<?php

/**
 * カードレイアウト用テンプレート
 * @var array $all_comments
 * @var array $replies
 */
?>
<div id="youtube-comments-to-wp" class="p_youtubecommentstowp _card">
  <?php foreach ($all_comments as $c): ?>
    <div class="p_youtubecommentstowp__topcomment">
      <div class="p_youtubecommentstowp__item">
        <div class="p_youtubecommentstowp__head">
          <?php if (!empty($c['author_icon'])): ?>
            <img class="p_youtubecommentstowp__icon" src="<?php echo esc_attr($c['author_icon']); ?>" alt="icon" width="40" height="40">
          <?php endif; ?>
          <div class="p_youtubecommentstowp__meta">
            <strong class="p_youtubecommentstowp__name"><?php echo esc_html($c['author']); ?></strong>
            <span class="p_youtubecommentstowp__date"><?php echo esc_html(date('Y.m.d', strtotime($c['published_at']))); ?></span>
          </div>
        </div>
        <div class="p_youtubecommentstowp__content">
          <div class="p_youtubecommentstowp__text"><?php echo nl2br(esc_html(str_replace('<br>', "\n", $c['text']))); ?></div>
        </div>
        <div class="p_youtubecommentstowp__more">
          <a class="" href="https://www.youtube.com/watch?v=<?php echo esc_attr($c['video_id']); ?>" target="_blank" rel="noopener noreferrer">
            <img src="<?php echo plugins_url('/../images/youtube-brands.svg', __FILE__); ?>" alt="" width="546" height="384">
            動画を見る
          </a>
        </div>
        <?php if (!empty($replies[$c['comment_id']])): ?>
          <div class="p_youtubecommentstowp__replys">
            <?php foreach ($replies[$c['comment_id']] as $r): ?>
              <div class="p_youtubecommentstowp__item">
                <div class="p_youtubecommentstowp__head">
                  <?php if (!empty($r['author_icon'])): ?>
                    <img class="p_youtubecommentstowp__icon" src="<?php echo esc_attr($r['author_icon']); ?>" alt="icon" width="40" height="40">
                  <?php endif; ?>
                  <div class="p_youtubecommentstowp__meta">
                    <strong class="p_youtubecommentstowp__name"><?php echo esc_html($r['author']); ?></strong>
                    <span class="p_youtubecommentstowp__date"><?php echo esc_html(date('Y.m.d', strtotime($r['published_at']))); ?></span>
                  </div>
                </div>
                <div class="p_youtubecommentstowp__content">
                  <div class="p_youtubecommentstowp__text"><?php echo nl2br(esc_html(str_replace('<br>', "\n", $c['text']))); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>