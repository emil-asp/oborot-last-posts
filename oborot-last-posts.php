<?php
/**
 * Plugin Name: Oborot Last posts
 * Description: Тестовое задание ( custom fields and last posts )
 * Version: 1.0
 *
 * Created by PhpStorm.
 * Author: emilasp
 * Date: 19.01.15
 * Time: 23:56
 */

// На всякий случай
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

if (!class_exists('OborotLastPosts')) {

    /**
     * Class OborotCustomFieldsTest
     * Создаём в админке мета бокс и пользовательские поля для записи и вывода источника материала
     * За ранее извиняюсь за неадекватное имя класса - не хватило фантазии)))
     */
    class OborotLastPosts {

        /**
         * @var string Наименование плагина - используется в плагине
         */
        private $plugin_name;

        /**
         * @var string Url до плагина - для регистрации ассетов
         */
        private $plugin_url;

        /**
         * Коснструктор
         */
        public function OborotLastPosts()
        {
            define('OborotLastPosts', true); // Объявляем константу инициализации нашего плагина

            $this->plugin_name = plugin_basename(__FILE__);
            $this->plugin_url = trailingslashit(WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));

            add_action('wp_print_scripts', [ &$this, 'registerScripts']); //публикуем скрипты
            add_action('wp_print_scripts', [ &$this, 'registerStyles']); //публикуем css

            register_activation_hook( $this->plugin_name, [ &$this, 'activate'] ); // Стандартно вешаем на активацию плагина
            register_deactivation_hook( $this->plugin_name, [ &$this, 'deactivate'] ); // Вешаем на деактивацию плагина
            register_uninstall_hook( $this->plugin_name, [ &$this, 'uninstall'] ); // Вешаем на удаление плагина

            add_action('add_meta_boxes', [ &$this, 'addMetaBox']); // Добавляем Мета бокс
            add_action('save_post', [ &$this, 'saveCustomFields'], 0); // Сохраняем пользовательские поля для записи

            add_action('oborot_get_lat_posts', [ &$this, 'showLastPosts']); // Хук на действие oborot_get_lat_posts - выводим десять записей
            add_action('oborot_get_last_posts', [ &$this, 'showLastPosts']); // Возможно что в распечатанном задании ошибка, я на всякий случай добавил и на это действие
        }

        /**
         * Регистрируем мета бокс
         */
        public function addMetaBox(){
            add_meta_box('metatest', 'Источник поста', [ &$this, 'fieldsTemplate'], 'post', 'side', 'default');
        }

        /**
         * Добавляем в метабокс кастомные поля
         */
        public function fieldsTemplate(){
            global $post;

            $custom_meta    = get_post_custom($post->ID);

            $sourceName     = $custom_meta["sourceName"][0];
            $sourceLink     = $custom_meta["sourceLink"][0];
            $sourceEnabled  = isset($custom_meta['sourceEnabled']);

            ?>
            <div class="custom-box">
                <label for="sourceName">
                    <strong>Источник:</strong><br>
                    <input type="text" name="sourceName" placeholder="сайт/орг." value="<?= $sourceName; ?>" <?= (!$sourceEnabled)?'class="disabledField"':'' ?> /><br>
                </label>
                <label for="sourceLink">
                    <strong>Ссылка:</strong><br>
                    <input type="text" name="sourceLink" placeholder="http://" value="<?= $sourceLink; ?>" <?= (!$sourceEnabled)?'class="disabledField"':'' ?> /><br>
                </label>
                <label for="sourceEnabled"><strong>Публиковать:</strong>
                    <input type="checkbox" name="sourceEnabled" class="oborot-enabled-custom" <?= ($sourceEnabled)?'checked="checked"':'' ?> />
                </label>
                <input type="hidden" name="extra_fields_nonce" value="<?= wp_create_nonce(__FILE__);  ?>" /> <!-- csrf токен ?>
            </div>
            <?php
        }

        /**
         * Сохраняем пользовательские поля в БД
         * @param $post_id
         * @return bool
         */
        public function saveCustomFields( $post_id ){
            if ( !wp_verify_nonce($_POST['extra_fields_nonce'], __FILE__) ) return false; // проверка csrf токена
            if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return false; // выходим если это автосохранение
            if ( !current_user_can('edit_post', $post_id) ) return false; // выходим если юзер не имеет право редактировать запись
            global $post;
            update_post_meta($post->ID, "sourceName", $_POST["sourceName"]);
            update_post_meta($post->ID, "sourceLink", $_POST["sourceLink"]);

            if( isset($_POST['sourceEnabled']) ){
                update_post_meta($post->ID, "sourceEnabled", $_POST["sourceEnabled"] );
            }else{
                delete_post_meta($post->ID, "sourceEnabled");
            }

        }



        /**
         * Регистрируем скрипты для плагина
         */
        public function registerScripts()
        {
            wp_register_script('OborotLastPosts', $this->plugin_url . 'assets/js/admin-scripts.js' ); // Регистрируем скрипты
            wp_enqueue_script('OborotLastPosts'); // Добавляем скрипты на страницу
        }

        /**
         * Регистрируем файлы со стилями для плагина
         */
        public function registerStyles()
        {
            wp_register_style('OborotLastPosts', $this->plugin_url . 'assets/css/style.css' );  // Регистрируем стили
            wp_enqueue_style('OborotLastPosts'); // Добавляем стили
        }

        /**
        * Выводим последние десять постов с кастомными полями - вешаем на дейтвие из тестового задания
        */
        public function showLastPosts(){

            global $post;
            $params = [
                    'posts_per_page'  => 10,
                    'orderby'         => 'post_date',
                    'order'           => 'DESC',
                    'post_type'       => 'post',
                    'post_status'     => 'publish'
                ];
            $lastPosts = new WP_Query($params);

            if($lastPosts->have_posts()){
                while ($lastPosts->have_posts()) {

                    $lastPosts->the_post();

                    $enabledSource  = get_post_meta($post->ID, 'sourceEnabled', true);
                    $sourceName     = get_post_meta($post->ID, 'sourceName', true);
                    $sourceLink     = get_post_meta($post->ID, 'sourceLink', true);

                    ?><!-- -->
                    <article id="post-<?php the_ID(); ?>" class="oborot-last-posts-article">
                        <header class="entry-header">
                            <h1 class="entry-title">
                                <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
                            </h1>
                        </header>
                        <div class="entry-summary">
                            <?php the_excerpt() ?>
                        </div>
                        <?php if( $enabledSource ): ?>
                        <footer class="entry-meta">
                            <div class="float-left"><strong>Опубликовано: </strong><?= get_the_time('j F Y, H:i');  ?></div>
                            <div class="float-right align-right"><strong>Источник: </strong><a href="<?= $sourceLink ?>"><?= $sourceName ?></a></div>
                            <div class="clearfix"></div>
                        </footer>
                        <?php endif; ?>
                    </article>
                <?php
                }
            } else {
                echo wpautop( 'Постов для вывода не найдено.' );
            }

            wp_reset_postdata();

        }


        /**
         * Активация плагина
         * @return bool
         */
        public function activate(){ return true; }

        /**
         * Деактивация плагина
         * @return bool
         */
        public function deactivate() { return true; }

        /**
         * Удаление плагина
         * @return bool
         */
        public function uninstall() { return true; }
    }
}

global $oborotTest;
$oborotTest = new OborotLastPosts();