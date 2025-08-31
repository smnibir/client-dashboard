<div class="common-padding common-bg common-radius common-border" style="
    margin: 2rem;
    border-radius: 10px;">
    <div class="common-padding">
        <?php
if (!empty($acf_name) && !empty($acf_content)) {
    
    echo wp_kses_post(wpautop($acf_content));
} else {
    echo '<p>No content found for this tab.</p>';
}
?>
    </div>

</div>