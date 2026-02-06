<script>
    const AVAILABLE_PAGES = <?php echo json_encode($all_pages); ?>;
    const siteSettings = <?php echo json_encode($site_settings); ?>;
    
    let navLinks = <?php echo json_encode($site_settings['nav_links'] ?? []); ?>;
    let navButtons = <?php echo json_encode($site_settings['nav_buttons'] ?? []); ?>;
    let footerData = <?php echo json_encode($site_settings['footer'] ?? get_default_site_settings()['footer']); ?>;
    
    let blocks = [];

    <?php if ($tab === 'index'): ?>
        blocks = <?php echo json_encode($index_data['blocks'] ?? $default_index_blocks); ?>;
    <?php elseif ($tab === 'new'): ?>
        blocks = <?php echo json_encode($default_product_blocks); ?>;
    <?php elseif ($tab === 'edit' && $edit_product): ?>
        blocks = <?php echo json_encode($edit_product['blocks'] ?? $default_product_blocks); ?>;
    <?php endif; ?>
</script>