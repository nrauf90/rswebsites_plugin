<?php get_header(); ?>
    <div class="website-wrapper">
        <?php while (have_posts()):the_post(); ?>
            <div class="website-page-content">
                <h2><?php the_title(); ?></h2>
                <div class="content">
                    <?php the_content(); ?>
                </div>
            </div>
        <?php endwhile; ?>
        <div class="website-from-wrapper">
            <div class="form">
                <h3>Add New Website</h3>
                <form action="<?php echo admin_url('admin-ajax.php'); ?>?action=add_new_website" id="website-form">
	                <?php wp_nonce_field(); ?>
                    <div class="response-result"></div>
                    <div class="form-field">
                        <label for="name">Name:</label>
                        <input id="name" class="input-name" name="name" type="text" required>
                    </div>
                    <div class="form-field">
                        <label for="url">Website URL:</label>
                        <input id="url" class="input-url" name="url" type="url" required>
                    </div>
                    <div class="buttons">
                        <input type="submit" class="submit" value="Add Website">
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php get_footer();


