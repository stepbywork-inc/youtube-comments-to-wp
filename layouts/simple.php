<?php

/**
 * シンプルレイアウト用テンプレート
 * @var array $all_comments
 * @var array $replies
 */
?>
<div id="youtube-comments-to-wp" class="p_youtubecommentstowp">
  <?php foreach ($all_comments as $c): ?>
    <div class="p_youtubecommentstowp__row">
      <a class="p_youtubecommentstowp__link" href="https://www.youtube.com/watch?v=<?php echo esc_attr($c['video_id']); ?>" target="_blank" rel="noopener noreferrer"></a>
      <div class="p_youtubecommentstowp__toplevel">
        <div class="p_youtubecommentstowp__comment">
          <?php if (!empty($c['author_icon'])): ?>
            <img class="p_youtubecommentstowp__icon" src="<?php echo esc_attr($c['author_icon']); ?>" alt="icon" width="36" height="36">
          <?php endif; ?>
          <div class="p_youtubecommentstowp__content">
            <div class="p_youtubecommentstowp__meta">
              <span class="p_youtubecommentstowp__author"><?php echo esc_html($c['author']); ?></span>
              <span class="p_youtubecommentstowp__date"><?php echo esc_html(date('Y.m.d', strtotime($c['published_at']))); ?></span>
            </div>
            <div class="p_youtubecommentstowp__text">
              <?php echo $c['text']; ?>
            </div>
          </div>
        </div>
        <!-- <img class="p_youtubecommentstowp__thumb" src="http://img.youtube.com/vi/<?php echo esc_attr($c['video_id']); ?>/mqdefault.jpg" alt=""> -->
      </div>
      <?php if (!empty($replies[$c['comment_id']])): ?>
        <div class="p_youtubecommentstowp__replys">
          <?php foreach ($replies[$c['comment_id']] as $r): ?>
            <div class="p_youtubecommentstowp__comment">
              <?php if (!empty($r['author_icon'])): ?>
                <img class="p_youtubecommentstowp__icon" src="<?php echo esc_attr($r['author_icon']); ?>" alt="icon" width="28" height="28">
              <?php endif; ?>
              <div class="p_youtubecommentstowp__content">
                <div class="p_youtubecommentstowp__meta">
                  <span class="p_youtubecommentstowp__author"><?php echo esc_html($r['author']); ?></span>
                  <span class="p_youtubecommentstowp__date"><?php echo esc_html(date('Y.m.d', strtotime($r['published_at']))); ?></span>
                </div>
                <div class="p_youtubecommentstowp__text">
                  <?php echo $r['text']; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>