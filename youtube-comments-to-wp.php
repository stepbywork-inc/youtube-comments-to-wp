<?php
/*
Plugin Name: YouTube Comments to WP
Description: 指定したYouTubeチャンネルのコメント一覧を取得し、WordPress管理画面で表示します。
Version: 0.2
Author: 株式会社ステップバイワーク
*/

if (!defined('ABSPATH')) exit;

class YouTube_Comments_To_WP
{
  // ショートコードでフロント出力
  public function __construct()
  {
    self::register_activation();
    add_action('admin_menu', [$this, 'add_admin_menu']);
    add_action('admin_init', [$this, 'register_settings']);
    // 外部ファイルのショートコード関数を登録
    require_once __DIR__ . '/inc/shortcode.php';
    add_shortcode('youtube_comments', 'youtube_comments_to_wp_shortcode');
  }

  public function add_admin_menu()
  {
    add_menu_page(
      'YouTubeコメント取得',
      'YouTubeコメント',
      'manage_options',
      'youtube-comments-to-wp',
      [$this, 'admin_page'],
      'dashicons-format-chat'
    );
  }

  public function register_settings()
  {
    register_setting('youtube_comments_to_wp_options', 'youtube_comments_to_wp_hidden_video_ids');
    register_setting('youtube_comments_to_wp_options', 'youtube_comments_to_wp_hidden_user_ids');
    register_setting('youtube_comments_to_wp_options', 'youtube_comments_to_wp_hidden_comment_ids');
    register_setting('youtube_comments_to_wp_options', 'youtube_comments_to_wp_api_key');
    register_setting('youtube_comments_to_wp_options', 'youtube_comments_to_wp_channel_id');
    register_setting('youtube_comments_to_wp_options', 'youtube_comments_to_wp_debug_mode');
  }

  // 指定コメントIDの全返信を取得
  private function get_all_replies($parentId, $apiKey, $videoId)
  {
    $replies = [];
    $pageToken = '';
    do {
      $url = 'https://www.googleapis.com/youtube/v3/comments?key=' . $apiKey . '&parentId=' . $parentId . '&part=snippet&maxResults=100';
      if ($pageToken) $url .= '&pageToken=' . $pageToken;
      $res = json_decode(@file_get_contents($url), true);
      if (isset($res['items'])) {
        foreach ($res['items'] as $reply) {
          $s = $reply['snippet'];
          $replies[] = [
            'id' => $reply['id'],
            'video_id' => $videoId,
            'text' => $s['textDisplay'],
            'author' => $s['authorDisplayName'],
            'author_id' => $s['authorChannelId']['value'] ?? '',
            'author_icon' => $s['authorProfileImageUrl'],
            'published_at' => $s['publishedAt'],
            'like_count' => $s['likeCount'] ?? 0,
            'replies' => []
          ];
        }
      }
      $pageToken = isset($res['nextPageToken']) ? $res['nextPageToken'] : '';
    } while ($pageToken);
    return $replies;
  }

  // チャンネル全体の最新コメントを直接取得
  private function get_channel_comments($channelId, $apiKey)
  {
    $comments = [];
    $pageToken = '';
    do {
      $url = 'https://www.googleapis.com/youtube/v3/commentThreads?key=' . $apiKey . '&allThreadsRelatedToChannelId=' . $channelId . '&part=snippet,replies&maxResults=100&order=time';
      if ($pageToken) $url .= '&pageToken=' . $pageToken;
      $res = json_decode(@file_get_contents($url), true);
      if (isset($res['items'])) {
        foreach ($res['items'] as $item) {
          $top = $item['snippet']['topLevelComment']['snippet'];
          $videoId = $item['snippet']['videoId'] ?? '';
          $replies = [];
          if (!empty($item['snippet']['totalReplyCount']) && $item['snippet']['totalReplyCount'] > 5) {
            // 返信が5件超なら全件取得
            $replies = $this->get_all_replies(
              $item['snippet']['topLevelComment']['id'],
              $apiKey,
              $videoId
            );
          } elseif (!empty($item['replies']['comments'])) {
            // 5件以下はそのまま（DB保存用のキーで統一）
            foreach ($item['replies']['comments'] as $reply) {
              $s = $reply['snippet'];
              $replies[] = [
                'id' => $reply['id'],
                'video_id' => $videoId,
                'text' => $s['textDisplay'],
                'author' => $s['authorDisplayName'],
                'author_id' => $s['authorChannelId']['value'] ?? '',
                'author_icon' => $s['authorProfileImageUrl'],
                'published_at' => $s['publishedAt'],
                'like_count' => $s['likeCount'] ?? 0,
                'replies' => []
              ];
            }
          }
          $comments[] = [
            'id' => $item['snippet']['topLevelComment']['id'],
            'video_id' => $videoId,
            'text' => $top['textDisplay'],
            'author' => $top['authorDisplayName'],
            'author_id' => $top['authorChannelId']['value'] ?? '',
            'author_icon' => $top['authorProfileImageUrl'],
            'published_at' => $top['publishedAt'],
            'like_count' => $top['likeCount'] ?? 0,
            'replies' => $replies
          ];
        }
      }
      $pageToken = isset($res['nextPageToken']) ? $res['nextPageToken'] : '';
    } while ($pageToken);
    return $comments;
  }

  // プラグイン有効化時にテーブル作成
  public static function activate()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'youtube_comments';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      comment_id varchar(64) NOT NULL,
      video_id varchar(32) NOT NULL,
      text longtext NOT NULL,
      author varchar(255) NOT NULL,
      author_id varchar(64) NOT NULL,
      author_icon varchar(255) DEFAULT '',
      published_at datetime NOT NULL,
      like_count int DEFAULT 0,
      parent_id varchar(64) DEFAULT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY comment_id (comment_id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  // プラグイン有効化フック登録
  public static function register_activation()
  {
    register_activation_hook(__FILE__, [__CLASS__, 'activate']);
  }

  public function admin_page()
  {
    $hidden_video_ids = get_option('youtube_comments_to_wp_hidden_video_ids', '');
    $hidden_user_ids = get_option('youtube_comments_to_wp_hidden_user_ids', '');
    $hidden_comment_ids = get_option('youtube_comments_to_wp_hidden_comment_ids', '');
    $hidden_video_ids_arr = array_filter(array_map('trim', explode("\n", $hidden_video_ids)));
    $hidden_user_ids_arr = array_filter(array_map('trim', explode("\n", $hidden_user_ids)));
    $hidden_comment_ids_arr = array_filter(array_map('trim', explode("\n", $hidden_comment_ids)));
    $api_key = get_option('youtube_comments_to_wp_api_key');
    $channel_id = get_option('youtube_comments_to_wp_channel_id');
    $comments = [];
    $info = '';
    $debug_mode = get_option('youtube_comments_to_wp_debug_mode');
    global $wpdb;
    $table_name = $wpdb->prefix . 'youtube_comments';
    $new_count = 0;

    // コメント取得処理
    if (isset($_POST['youtube_get_comments'])) {
      if (!wp_verify_nonce($_POST['youtube_get_comments_nonce'], 'youtube_get_comments_action')) {
        $info = '不正なリクエストです。';
      } elseif (!$api_key || !$channel_id) {
        $info = 'APIキーとチャンネルIDを入力してください。';
      } else {
        $all_comments = $this->get_channel_comments($channel_id, $api_key);
        if ($debug_mode) {
          echo '<pre style="background:#fff;color:#000;max-height:400px;overflow:auto;">';
          var_dump($all_comments);
          echo '</pre>';
        }
        $inserted = 0;
        foreach ($all_comments as $c) {
          // トップレベル
          $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE comment_id = %s", $c['id']));
          if (!$exists) {
            $wpdb->insert($table_name, [
              'comment_id' => $c['id'],
              'video_id' => $c['video_id'],
              'text' => $c['text'],
              'author' => $c['author'],
              'author_id' => $c['author_id'],
              'author_icon' => $c['author_icon'],
              'published_at' => date('Y-m-d H:i:s', strtotime($c['published_at'])),
              'like_count' => $c['like_count'] ?? 0,
              'parent_id' => null
            ]);
            $inserted++;
          }
          // 返信
          if (!empty($c['replies'])) {
            foreach ($c['replies'] as $r) {
              $exists_r = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE comment_id = %s", $r['id']));
              if (!$exists_r) {
                if ($debug_mode) error_log('Insert reply: ' . $r['id']);
                $result = $wpdb->insert($table_name, [
                  'comment_id' => $r['id'],
                  'video_id' => $r['video_id'],
                  'text' => $r['text'],
                  'author' => $r['author'],
                  'author_id' => $r['author_id'],
                  'author_icon' => $r['author_icon'],
                  'published_at' => date('Y-m-d H:i:s', strtotime($r['published_at'])),
                  'like_count' => $r['like_count'] ?? 0,
                  'parent_id' => $c['id']
                ]);
                if (!$result) {
                  if ($debug_mode) {
                    echo '<div style="color:red;">リプライ保存失敗: ' . esc_html($r['id']) . ' / ' . esc_html($wpdb->last_error) . '</div>';
                    error_log('Insert reply failed: ' . $r['id'] . ' / ' . $wpdb->last_error);
                  }
                }
                $inserted++;
              }
            }
          }
        }
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $info = '新規コメント: ' . $inserted . '件を保存しました。合計: ' . $total . '件（独自テーブル）';
      }
    }

    // データ削除処理
    if (isset($_POST['youtube_delete_json'])) {
      if (!wp_verify_nonce($_POST['youtube_delete_json_nonce'], 'youtube_delete_json_action')) {
        $info = '不正なリクエストです。';
      } else {
        $wpdb->query("TRUNCATE TABLE $table_name");
        $info = 'コメントデータを削除しました。';
        $comments = [];
      }
    }
    // 表示用：独自テーブルから取得
    if (empty($comments)) {
      $rows = $wpdb->get_results("SELECT * FROM $table_name ORDER BY published_at DESC", ARRAY_A);
      // トップレベルのみ抽出
      $comments = [];
      $replies_map = [];
      foreach ($rows as $row) {
        if ($row['parent_id']) {
          $replies_map[$row['parent_id']][] = $row;
        } else {
          $comments[] = $row;
        }
      }
      // 返信をネスト
      foreach ($comments as &$c) {
        $c['replies'] = $replies_map[$c['comment_id']] ?? [];
      }
      unset($c);
    }

?>
    <div class="wrap">
      <h1>YouTubeコメント取得</h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('youtube_comments_to_wp_options');
        do_settings_sections('youtube_comments_to_wp_options');
        ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row">ショートコード</th>
            <td><input type="text" name="" value='[youtube_comments count="10" video_id="" layout="card" reply_setting="2"]' readonly size="100" />
          <br><span style="color:#888;">
            video_id→特定の動画だけの場合動画IDを入れる <br>
            layout→card/simple <br>
            reply_setting→ 0:親子, 1:リプライ含めず, 2:リプライもフラット
          </span>
          </td>
          </tr>
          <tr valign="top">
            <th scope="row">YouTube APIキー</th>
            <td><input type="text" name="youtube_comments_to_wp_api_key" value="<?php echo esc_attr($api_key); ?>" size="50" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">チャンネルID</th>
            <td><input type="text" name="youtube_comments_to_wp_channel_id" value="<?php echo esc_attr($channel_id); ?>" size="30" /></td>
          </tr>
          <tr valign="top">
            <th scope="row">デバッグモード</th>
            <td><input type="checkbox" name="youtube_comments_to_wp_debug_mode" value="1" <?php checked($debug_mode, '1'); ?> /> <span style="color:#888;">（APIレスポンスやエラーを表示）</span></td>
          </tr>
          <tr valign="top">
            <th scope="row">非表示動画ID</th>
            <td><textarea name="youtube_comments_to_wp_hidden_video_ids" rows="2" cols="50" placeholder="1行に1つずつ"><?php echo esc_textarea($hidden_video_ids); ?></textarea><br><span style="color:#888;">1行に1つずつ入力。該当動画IDのコメント・リプライを非表示</span></td>
          </tr>
          <tr valign="top">
            <th scope="row">非表示ユーザーID</th>
            <td><textarea name="youtube_comments_to_wp_hidden_user_ids" rows="2" cols="50" placeholder="1行に1つずつ"><?php echo esc_textarea($hidden_user_ids); ?></textarea><br><span style="color:#888;">1行に1つずつ入力。該当ユーザーIDのコメント・リプライを非表示</span></td>
          </tr>
          <tr valign="top">
            <th scope="row">非表示コメントID</th>
            <td><textarea name="youtube_comments_to_wp_hidden_comment_ids" rows="2" cols="50" placeholder="1行に1つずつ"><?php echo esc_textarea($hidden_comment_ids); ?></textarea><br><span style="color:#888;">1行に1つずつ入力。該当コメントIDのコメント・リプライを非表示</span></td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
      <hr>
      <?php if ($api_key && $channel_id): ?>
        <form method="post" style="display:inline-block; margin-right:1em;">
          <?php wp_nonce_field('youtube_get_comments_action', 'youtube_get_comments_nonce'); ?>
          <input type="submit" name="youtube_get_comments" class="button button-primary" value="コメント取得">
        </form>
      <?php endif; ?>
      <form method="post" style="display:inline-block;">
        <?php wp_nonce_field('youtube_delete_json_action', 'youtube_delete_json_nonce'); ?>
        <input type="submit" name="youtube_delete_json" class="button" value="データ削除" onclick="return confirm('本当に削除しますか？');">
      </form>
      <?php if ($info): ?>
        <div style="color:green; margin-top:1em;"> <?php echo esc_html($info); ?> </div>
      <?php endif; ?>
      <?php if ($comments): ?>
        <?php
        // 非表示ID該当判定用関数
        function is_hidden_comment($item, $hidden_video_ids_arr, $hidden_user_ids_arr, $hidden_comment_ids_arr)
        {
          return in_array($item['video_id'], $hidden_video_ids_arr, true)
            || in_array($item['author_id'], $hidden_user_ids_arr, true)
            || in_array($item['comment_id'], $hidden_comment_ids_arr, true);
        }
        $reply_count = 0;
        foreach ($comments as &$c) {
          if (!empty($c['replies'])) {
            $reply_count += count($c['replies']);
          }
        }
        unset($c);
        $total_count = count($comments) + $reply_count;
        $display_limit = 10;
        ?>
        <style>
          .yt-comment-hidden {
            opacity: 0.4;
            pointer-events: none;
            filter: grayscale(60%);
          }
        </style>
        <h2>
          コメント一覧（合計: <?php echo $total_count; ?>件 /
          トップレベル: <?php echo count($comments); ?>件 /
          リプライ: <?php echo $reply_count; ?>件）
        </h2>
        <ul id="yt-comments-list">
          <?php foreach ($comments as $i => $c): ?>
            <?php $is_hidden = is_hidden_comment($c, $hidden_video_ids_arr, $hidden_user_ids_arr, $hidden_comment_ids_arr); ?>
            <li class="yt-comment-item<?php if ($is_hidden) echo ' yt-comment-hidden'; ?>" style="margin-bottom:1em;<?php if ($i >= $display_limit) echo 'display:none;'; ?>">
              <div style="display:flex;align-items:center;gap:10px;">
                <?php if (!empty($c['author_icon'])): ?>
                  <img src="<?php echo esc_attr($c['author_icon']); ?>" alt="icon" width="36" height="36" style="border-radius:50%;background:#eee;">
                <?php endif; ?>
                <strong><?php echo esc_html($c['author']); ?></strong> (<?php echo esc_html(date('Y.m.d', strtotime($c['published_at']))); ?>)
              </div>
              <div style="margin:4px 0 4px 0;">
                <span style="color:#888;">コメントID: <?php echo esc_html($c['comment_id']); ?></span>
                ／
                <span style="color:#888;">動画ID: <?php echo esc_html($c['video_id']); ?></span>
                ／
                <span style="color:#888;">ユーザーID: <?php echo esc_html($c['author_id']); ?></span>
              </div>
              <div><?php echo $c['text']; ?></div>
              <?php if (!empty($c['replies'])): ?>
                <ul style="margin-left:2em; margin-top:0.5em; border-left:2px solid #eee; padding-left:1em;">
                  <?php foreach ($c['replies'] as $r): ?>
                    <?php $is_hidden_r = is_hidden_comment($r, $hidden_video_ids_arr, $hidden_user_ids_arr, $hidden_comment_ids_arr); ?>
                    <li class="<?php if ($is_hidden_r) echo 'yt-comment-hidden'; ?>" style="margin-bottom:0.5em;">
                      <div style="display:flex;align-items:center;gap:8px;">
                        <?php if (!empty($r['author_icon'])): ?>
                          <img src="<?php echo esc_attr($r['author_icon']); ?>" alt="icon" width="28" height="28" style="border-radius:50%;background:#eee;">
                        <?php endif; ?>
                        <strong><?php echo esc_html($r['author']); ?></strong> (<?php echo esc_html(date('Y.m.d', strtotime($r['published_at']))); ?>)
                      </div>
                      <div style="margin:2px 0 2px 0;">
                        <span style="color:#888;">コメントID: <?php echo esc_html($r['comment_id']); ?></span>
                        ／
                        <span style="color:#888;">ユーザーID: <?php echo esc_html($r['author_id']); ?></span>
                      </div>
                      <div><?php echo $r['text']; ?></div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if (count($comments) > $display_limit): ?>
          <div style="text-align:center; margin:1em 0;">
            <button id="yt-comments-more" class="button">もっと見る</button>
          </div>
          <script>
            (function() {
              let shown = <?php echo $display_limit; ?>;
              const total = <?php echo count($comments); ?>;
              const step = <?php echo $display_limit; ?>;
              const items = document.querySelectorAll('#yt-comments-list .yt-comment-item');
              document.getElementById('yt-comments-more').addEventListener('click', function(e) {
                e.preventDefault();
                let next = shown + step;
                for (let i = shown; i < next && i < total; i++) {
                  if (items[i]) items[i].style.display = '';
                }
                shown = next;
                if (shown >= total) {
                  this.style.display = 'none';
                }
              });
            })();
          </script>
        <?php endif; ?>
      <?php endif; ?>
    </div>
<?php
  }
}

new YouTube_Comments_To_WP();

// ===== フロント用CSS読み込み =====
add_action('wp_enqueue_scripts', function () {
  $url = plugins_url('css/style.css?', __FILE__);
  wp_enqueue_style('youtube-comments-to-wp', $url, [], null);
});
