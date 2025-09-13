<?php
// ショートコード本体
function youtube_comments_to_wp_shortcode($atts)
{
  $atts = shortcode_atts([
    'count' => 10,
    'video_id' => '',
    'layout' => 'simple',
    'reply_setting' => '0', // 0:親子, 1:リプライ含めず, 2:リプライもフラット
  ], $atts);
  $layout = $atts['layout'];
  $count = intval($atts['count']);
  $video_id = trim($atts['video_id']);
  $reply_setting = (string)($atts['reply_setting']);
  $hidden_video_ids = get_option('youtube_comments_to_wp_hidden_video_ids', '');
  $hidden_user_ids = get_option('youtube_comments_to_wp_hidden_user_ids', '');
  $hidden_comment_ids = get_option('youtube_comments_to_wp_hidden_comment_ids', '');
  $hidden_video_ids_arr = array_filter(array_map('trim', explode("\n", $hidden_video_ids)));
  $hidden_user_ids_arr = array_filter(array_map('trim', explode("\n", $hidden_user_ids)));
  $hidden_comment_ids_arr = array_filter(array_map('trim', explode("\n", $hidden_comment_ids)));
  global $wpdb;
  $table_name = $wpdb->prefix . 'youtube_comments';
  $top_comments = [];
  $replies = [];
  $all_comments = [];
  $params = [];
  // reply_setting=2:親もリプライも全て1クエリで取得
  if ($reply_setting === '2') {
    $where = [];
    if ($video_id) {
      $where[] = 'video_id = %s';
      $params[] = $video_id;
    }
    if (!empty($hidden_video_ids_arr)) {
      $where[] = 'video_id NOT IN (' . implode(',', array_fill(0, count($hidden_video_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_video_ids_arr);
    }
    if (!empty($hidden_user_ids_arr)) {
      $where[] = 'author_id NOT IN (' . implode(',', array_fill(0, count($hidden_user_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_user_ids_arr);
    }
    if (!empty($hidden_comment_ids_arr)) {
      $where[] = 'comment_id NOT IN (' . implode(',', array_fill(0, count($hidden_comment_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_comment_ids_arr);
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT * FROM $table_name $where_sql ORDER BY published_at DESC LIMIT %d";
    $params[] = $count;
    $all_comments = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
  } else if ($reply_setting === '1') {
    // 親のみ
    $where = [];
    if ($video_id) {
      $where[] = 'video_id = %s';
      $params[] = $video_id;
    }
    $where[] = 'parent_id IS NULL';
    if (!empty($hidden_video_ids_arr)) {
      $where[] = 'video_id NOT IN (' . implode(',', array_fill(0, count($hidden_video_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_video_ids_arr);
    }
    if (!empty($hidden_user_ids_arr)) {
      $where[] = 'author_id NOT IN (' . implode(',', array_fill(0, count($hidden_user_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_user_ids_arr);
    }
    if (!empty($hidden_comment_ids_arr)) {
      $where[] = 'comment_id NOT IN (' . implode(',', array_fill(0, count($hidden_comment_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_comment_ids_arr);
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT * FROM $table_name $where_sql ORDER BY published_at DESC LIMIT %d";
    $params[] = $count;
    $all_comments = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    $replies = [];
  } else {
    // 0:親子
    $where = [];
    if ($video_id) {
      $where[] = 'video_id = %s';
      $params[] = $video_id;
    }
    $where[] = 'parent_id IS NULL';
    if (!empty($hidden_video_ids_arr)) {
      $where[] = 'video_id NOT IN (' . implode(',', array_fill(0, count($hidden_video_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_video_ids_arr);
    }
    if (!empty($hidden_user_ids_arr)) {
      $where[] = 'author_id NOT IN (' . implode(',', array_fill(0, count($hidden_user_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_user_ids_arr);
    }
    if (!empty($hidden_comment_ids_arr)) {
      $where[] = 'comment_id NOT IN (' . implode(',', array_fill(0, count($hidden_comment_ids_arr), '%s')) . ')';
      $params = array_merge($params, $hidden_comment_ids_arr);
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT * FROM $table_name $where_sql ORDER BY published_at DESC LIMIT %d";
    $params[] = $count;
    $all_comments = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    // リプライ取得
    $comment_ids = array_column($all_comments, 'comment_id');
    if ($comment_ids) {
      $in = implode(",", array_fill(0, count($comment_ids), '%s'));
      $reply_where = ['parent_id IN (' . $in . ')'];
      $reply_params = $comment_ids;
      if (!empty($hidden_video_ids_arr)) {
        $reply_where[] = 'video_id NOT IN (' . implode(',', array_fill(0, count($hidden_video_ids_arr), '%s')) . ')';
        $reply_params = array_merge($reply_params, $hidden_video_ids_arr);
      }
      if (!empty($hidden_user_ids_arr)) {
        $reply_where[] = 'author_id NOT IN (' . implode(',', array_fill(0, count($hidden_user_ids_arr), '%s')) . ')';
        $reply_params = array_merge($reply_params, $hidden_user_ids_arr);
      }
      if (!empty($hidden_comment_ids_arr)) {
        $reply_where[] = 'comment_id NOT IN (' . implode(',', array_fill(0, count($hidden_comment_ids_arr), '%s')) . ')';
        $reply_params = array_merge($reply_params, $hidden_comment_ids_arr);
      }
      $reply_where_sql = $reply_where ? ('WHERE ' . implode(' AND ', $reply_where)) : '';
      $sql_r = "SELECT * FROM $table_name $reply_where_sql ORDER BY published_at ASC";
      $reply_rows = $wpdb->get_results($wpdb->prepare($sql_r, ...$reply_params), ARRAY_A);
      foreach ($reply_rows as $row) {
        $replies[$row['parent_id']][] = $row;
      }
    }
  }
  $layout_file = __DIR__ . '/../layouts/' . basename($layout) . '.php';
  if (!file_exists($layout_file)) {
    return '<div style="color:red">レイアウトファイルが見つかりません: ' . esc_html($layout) . '</div>';
  }
  ob_start();
  include $layout_file;
  return ob_get_clean();
}
