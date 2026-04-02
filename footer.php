<footer class="site-footer">
    <div class="site-footer__inner">

        <p class="site-footer__name"><?php bloginfo('name'); ?></p>

        <nav class="site-footer__nav" aria-label="Sidfotsmeny">
            <?php wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'footer-nav-list',
                'fallback_cb'    => false,
            ]); ?>
        </nav>

        <p class="site-footer__copy">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>

    </div>
</footer>

<?php wp_footer(); ?>

<script>
// Hamburgermeny – öppna/stäng nav på mobil
(function () {
    var btn   = document.querySelector('.site-header__menu-btn');
    var close = document.querySelector('.nav-mobile-close');
    var nav   = document.querySelector('.site-header__nav');
    if (!btn || !nav) return;

    function openNav() {
        nav.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('nav-is-open');
    }
    function closeNav() {
        nav.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('nav-is-open');
    }

    btn.addEventListener('click', openNav);
    if (close) close.addEventListener('click', closeNav);
})();
</script>

</body>
</html>
