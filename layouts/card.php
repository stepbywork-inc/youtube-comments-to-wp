<div id="youtube-comments-to-wp" class="p_youtubecommentstowp_fukidashi">
  <?php foreach ($all_comments as $c): ?>
    <div class="p_youtubecommentstowp_fukidashi__row">
      <a class="p_youtubecommentstowp_fukidashi__link" href="https://www.youtube.com/watch?v=<?php echo esc_attr($c['video_id']); ?>" target="_blank" rel="noopener noreferrer"></a>
      <div class="p_youtubecommentstowp_fukidashi__comment">
        <div class="p_youtubecommentstowp_fukidashi__meta">
          <?php if (!empty($c['author_icon'])): ?>
            <img class="p_youtubecommentstowp_fukidashi__icon" src="<?php echo esc_attr($c['author_icon']); ?>" alt="icon" width="36" height="36">
          <?php endif; ?>
          <span class="p_youtubecommentstowp_fukidashi__author"><?php echo esc_html($c['author']); ?></span>
          <span class="p_youtubecommentstowp_fukidashi__date"><?php echo esc_html(date('Y.m.d', strtotime($c['published_at']))); ?></span>
        </div>
        <div class="p_youtubecommentstowp_fukidashi__text">
          <?php echo $c['text']; ?>
        </div>
      </div>
      <?php if (!empty($replies[$c['comment_id']])): ?>
        <div class="p_youtubecommentstowp_fukidashi__replys">
          <?php foreach ($replies[$c['comment_id']] as $r): ?>
            <div class="p_youtubecommentstowp_fukidashi__comment">
              <div class="p_youtubecommentstowp_fukidashi__meta">
                <?php if (!empty($r['author_icon'])): ?>
                  <img class="p_youtubecommentstowp_fukidashi__icon" src="<?php echo esc_attr($r['author_icon']); ?>" alt="icon" width="28" height="28">
                <?php endif; ?>
                <span class="p_youtubecommentstowp_fukidashi__author"><?php echo esc_html($r['author']); ?></span>
                <span class="p_youtubecommentstowp_fukidashi__date"><?php echo esc_html(date('Y.m.d', strtotime($r['published_at']))); ?></span>
              </div>
              <div class="p_youtubecommentstowp_fukidashi__text">
                <?php echo $r['text']; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>