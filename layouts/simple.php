<?php
/**
 * シンプルレイアウト用テンプレート
 * @var array $all_comments
 * @var array $replies
 */
?>
<ul class="yt-comments-front">
  <?php foreach ($all_comments as $c): ?>
    <li class="yt-comment-item" style="margin-bottom:1em;">
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
      <?php if (!empty($replies[$c['comment_id']])): ?>
        <ul style="margin-left:2em; margin-top:0.5em; border-left:2px solid #eee; padding-left:1em;">
          <?php foreach ($replies[$c['comment_id']] as $r): ?>
            <li style="margin-bottom:0.5em;">
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
