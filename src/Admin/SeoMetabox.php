<?php

namespace Glory\Admin;

class SeoMetabox
{
    private const META_TITLE = '_glory_seo_title';
    private const META_DESC = '_glory_seo_desc';
    private const META_CANONICAL = '_glory_seo_canonical';
    private const META_FAQ = '_glory_seo_faq'; // JSON array: [{q, a}]
    private const META_BREADCRUMB = '_glory_seo_breadcrumb'; // JSON array: [{name, url}]

    public function registerHooks(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetabox']);
        add_action('save_post_page', [$this, 'save'], 10, 2);
    }

    public function addMetabox(): void
    {
        add_meta_box(
            'glory_seo_box',
            'SEO (Glory)',
            [$this, 'render'],
            'page',
            'normal',
            'high'
        );
    }

    public function render(\WP_Post $post): void
    {
        $title = (string) get_post_meta($post->ID, self::META_TITLE, true);
        $desc = (string) get_post_meta($post->ID, self::META_DESC, true);
        $canonical = (string) get_post_meta($post->ID, self::META_CANONICAL, true);
        $faqJson = (string) get_post_meta($post->ID, self::META_FAQ, true);
        $bcJson = (string) get_post_meta($post->ID, self::META_BREADCRUMB, true);

        wp_nonce_field('glory_seo_save', 'glory_seo_nonce');

        echo '<div style="display:grid;gap:12px;">';
        echo '<label>Título SEO<br><input type="text" name="glory_seo_title" value="' . esc_attr($title) . '" style="width:100%"></label>';
        echo '<label>Meta descripción<br><textarea name="glory_seo_desc" rows="3" style="width:100%">' . esc_textarea($desc) . '</textarea></label>';
        echo '<label>URL canónica<br><input type="url" name="glory_seo_canonical" value="' . esc_attr($canonical) . '" placeholder="https://materialdepadel.com/.../" style="width:100%"></label>';

        echo '<hr><strong>FAQ (JSON-LD)</strong>';
        echo '<div id="glory_faq_wrap">';
        $faqArr = $this->decodeJson($faqJson);
        // Normalizar posibles cadenas con literales unicode tipo u00f3
        foreach ($faqArr as &$item) {
            if (isset($item['q'])) { $item['q'] = $this->normalizeUnicodeLiterals((string) $item['q']); }
            if (isset($item['a'])) { $item['a'] = $this->normalizeUnicodeLiterals((string) $item['a']); }
        }
        unset($item);
        if (empty($faqArr)) { $faqArr = []; }
        foreach ($faqArr as $i => $item) {
            $q = isset($item['q']) ? (string) $item['q'] : '';
            $a = isset($item['a']) ? (string) $item['a'] : '';
            echo '<div class="glory-faq-item" style="margin:8px 0;padding:8px;border:1px solid #ddd">';
            echo '<input type="text" name="glory_faq[' . $i . '][q]" value="' . esc_attr($q) . '" placeholder="Pregunta" style="width:100%;margin-bottom:6px">';
            echo '<textarea name="glory_faq[' . $i . '][a]" rows="2" placeholder="Respuesta" style="width:100%">' . esc_textarea($a) . '</textarea>';
            echo '<button type="button" class="button glory-del" data-target="faq" style="margin-top:6px">Eliminar</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="button" id="glory_add_faq">Añadir FAQ</button>';

        echo '<hr><strong>Breadcrumb (JSON-LD)</strong>';
        echo '<div id="glory_bc_wrap">';
        $bcArr = $this->decodeJson($bcJson);
        foreach ($bcArr as &$bcItem) {
            if (isset($bcItem['name'])) { $bcItem['name'] = $this->normalizeUnicodeLiterals((string) $bcItem['name']); }
        }
        unset($bcItem);
        if (empty($bcArr)) { $bcArr = []; }
        foreach ($bcArr as $i => $item) {
            $name = isset($item['name']) ? (string) $item['name'] : '';
            $url = isset($item['url']) ? (string) $item['url'] : '';
            echo '<div class="glory-bc-item" style="margin:8px 0;padding:8px;border:1px solid #ddd">';
            echo '<input type="text" name="glory_bc[' . $i . '][name]" value="' . esc_attr($name) . '" placeholder="Nombre" style="width:49%;margin-right:2%">';
            echo '<input type="url" name="glory_bc[' . $i . '][url]" value="' . esc_attr($url) . '" placeholder="https://..." style="width:49%">';
            echo '<button type="button" class="button glory-del" data-target="bc" style="margin-top:6px;display:block">Eliminar</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="button" id="glory_add_bc">Añadir breadcrumb</button>';

        echo '</div>';

        echo '<script>' . <<<'JS'
(function(){
  function addItem(wrapId, tplHtml){
    var w=document.getElementById(wrapId); if(!w)return; var tmp=document.createElement('div'); tmp.innerHTML=tplHtml; w.appendChild(tmp.firstChild);
  }
  function nextIndex(wrapId, cls){ var w=document.getElementById(wrapId); if(!w) return 0; return w.querySelectorAll('.'+cls).length; }
  document.getElementById('glory_add_faq')?.addEventListener('click', function(){
    var i=nextIndex('glory_faq_wrap','glory-faq-item');
    var tpl=`<div class="glory-faq-item" style="margin:8px 0;padding:8px;border:1px solid #ddd">
<input type="text" name="glory_faq[${i}][q]" placeholder="Pregunta" style="width:100%;margin-bottom:6px">
<textarea name="glory_faq[${i}][a]" rows="2" placeholder="Respuesta" style="width:100%"></textarea>
<button type="button" class="button glory-del" data-target="faq" style="margin-top:6px">Eliminar</button>
</div>`;
    addItem('glory_faq_wrap', tpl);
  });
  document.getElementById('glory_add_bc')?.addEventListener('click', function(){
    var i=nextIndex('glory_bc_wrap','glory-bc-item');
    var tpl=`<div class="glory-bc-item" style="margin:8px 0;padding:8px;border:1px solid #ddd">
<input type="text" name="glory_bc[${i}][name]" placeholder="Nombre" style="width:49%;margin-right:2%">
<input type="url" name="glory_bc[${i}][url]" placeholder="https://..." style="width:49%">
<button type="button" class="button glory-del" data-target="bc" style="margin-top:6px;display:block">Eliminar</button>
</div>`;
    addItem('glory_bc_wrap', tpl);
  });
  document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('glory-del')){
      var box=e.target.closest('.glory-faq-item, .glory-bc-item'); if(box) box.remove();
    }
  });
})();
JS
        . '</script>';
    }

    public function save(int $postId, \WP_Post $post): void
    {
        if (!isset($_POST['glory_seo_nonce']) || !wp_verify_nonce($_POST['glory_seo_nonce'], 'glory_seo_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_page', $postId)) {
            return;
        }

        $title = isset($_POST['glory_seo_title']) ? wp_strip_all_tags((string) $_POST['glory_seo_title']) : '';
        $desc = isset($_POST['glory_seo_desc']) ? sanitize_text_field((string) $_POST['glory_seo_desc']) : '';
        $canonical = isset($_POST['glory_seo_canonical']) ? esc_url_raw((string) $_POST['glory_seo_canonical']) : '';

        update_post_meta($postId, self::META_TITLE, $title);
        update_post_meta($postId, self::META_DESC, $desc);
        update_post_meta($postId, self::META_CANONICAL, $canonical);

        $faq = isset($_POST['glory_faq']) && is_array($_POST['glory_faq']) ? $_POST['glory_faq'] : [];
        $faqClean = [];
        foreach ($faq as $item) {
            $q = isset($item['q']) ? wp_strip_all_tags((string) $item['q']) : '';
            $a = isset($item['a']) ? wp_kses_post((string) $item['a']) : '';
            if ($q !== '' && $a !== '') { $faqClean[] = ['q' => $q, 'a' => $a]; }
        }
        update_post_meta($postId, self::META_FAQ, wp_json_encode($faqClean, JSON_UNESCAPED_UNICODE));

        $bc = isset($_POST['glory_bc']) && is_array($_POST['glory_bc']) ? $_POST['glory_bc'] : [];
        $bcClean = [];
        foreach ($bc as $item) {
            $name = isset($item['name']) ? wp_strip_all_tags((string) $item['name']) : '';
            $url = isset($item['url']) ? esc_url_raw((string) $item['url']) : '';
            if ($name !== '') { $bcClean[] = ['name' => $name, 'url' => $url]; }
        }
        update_post_meta($postId, self::META_BREADCRUMB, wp_json_encode($bcClean, JSON_UNESCAPED_UNICODE));
    }

    private function decodeJson(string $json): array
    {
        if ($json === '') { return []; }
        $arr = json_decode($json, true);
        if (is_array($arr)) { return $arr; }
        // Intento con des-escape \u00xx -> \u00xx
        $normalized = str_replace('\\u', '\\u', $json);
        $arr = json_decode($normalized, true);
        if (is_array($arr)) { return $arr; }
        return [];
    }

    private function normalizeUnicodeLiterals(string $text): string
    {
        // Convierte patrones uXXXX a su carácter unicode real
        return preg_replace_callback('/u([0-9a-fA-F]{4})/', function ($m) {
            $code = hexdec($m[1]);
            if ($code === 0) { return $m[0]; }
            $entity = '&#x' . strtoupper($m[1]) . ';';
            return html_entity_decode($entity, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }, $text);
    }
}


