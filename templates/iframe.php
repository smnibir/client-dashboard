<?php
if (preg_match('/\\((https?:\\/\\/reports\\.webgrowth\\.io\\/[^\\s]+)\\)/', $content, $match)) {
    $iframe_url = $match[1];
} elseif (preg_match('/\\((https?:\\/\\/forms\\.clickup\\.com\\/[^\\s]+)\\)/', $content, $match)) {
    $iframe_url = $match[1];
}
?>

<?php if ($iframe_url): ?>
    <iframe load="lazy" src="<?php echo esc_url($iframe_url); ?>" class="clickup-iframe" width="100%" style="height:100vh;" frameborder="0"></iframe>
<?php else: ?>
    <p>No valid report URL found in content.</p>
<?php endif; ?>
