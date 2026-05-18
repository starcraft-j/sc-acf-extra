# SC ACF Extra

ACF 無料版に Repeater / Flexible Content を追加する WordPress プラグイン。**ACF Pro と完全互換のメタ保存形式**で、後から Pro を有効化してもデータはそのまま動きます。

## ステータス

`v0.5.2` (2026-05-18) — Repeater + Flexible Content + ドラッグ並べ替え対応

## インストール

### A. Git pull (自社運用サイト向け)

サーバーに SSH で入って:

```bash
cd /path/to/wp-content/plugins
git clone git@github.com:starcraft-j/sc-acf-extra.git
wp plugin activate sc-acf-extra
```

更新は `git pull` のみ。Deploy key の登録手順は内部メモ参照。

### B. ZIP 配布 (クライアント納品向け)

[Releases](https://github.com/starcraft-j/sc-acf-extra/releases) から最新タグの ZIP をダウンロードして wp-admin の「プラグイン → 新規追加 → プラグインのアップロード」で導入。

## 使い方

プラグインを有効化すると、ACF のフィールドタイプセレクタに **「SC Repeater」** と **「SC Flexible Content」** が追加されます。

### A. wp-admin GUI でフィールドグループを作る (推奨・最短)

「ACF → フィールドグループ → 新規追加」から普通に作成。フィールドタイプで `SC Repeater` または `SC Flexible Content` を選ぶと、サブフィールドマネージャ UI が表示されます。

> 単一サイトでサクッと使うならこれで完結。`functions.php` を触る必要はありません。

### B. PHP コードで定義する (再利用・バージョン管理向け)

複数サイトで同じフィールドグループを共有したい / Git 管理したいときは `acf_add_local_field_group()` を使います。書式は ACF Pro と同一:

```php
add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }
    acf_add_local_field_group( array(
        'key'      => 'group_works',
        'title'    => '制作実績',
        'fields'   => array(
            array(
                'key'          => 'field_works_features',
                'label'        => '制作ポイント',
                'name'         => 'features',
                'type'         => 'sc_repeater',
                'min'          => 0,
                'max'          => 5,
                'button_label' => 'ポイントを追加',
                'sub_fields'   => array(
                    array( 'key' => 'field_features_title', 'label' => 'タイトル', 'name' => 'title', 'type' => 'text' ),
                    array( 'key' => 'field_features_body',  'label' => '内容',     'name' => 'body',  'type' => 'textarea' ),
                ),
            ),
        ),
        'location' => array(
            array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'works' ) ),
        ),
    ) );
} );
```

Flexible Content の例:

```php
array(
    'key'     => 'field_page_sections',
    'label'   => 'セクション',
    'name'    => 'sections',
    'type'    => 'sc_flexible',
    'layouts' => array(
        array(
            'key'        => 'layout_hero',
            'label'      => 'Hero',
            'name'       => 'hero',
            'sub_fields' => array(
                array( 'key' => 'field_hero_title', 'label' => 'タイトル', 'name' => 'title', 'type' => 'text' ),
                array( 'key' => 'field_hero_image', 'label' => '画像',     'name' => 'image', 'type' => 'image', 'return_format' => 'array' ),
            ),
        ),
        array(
            'key'        => 'layout_cta',
            'label'      => 'CTA',
            'name'       => 'cta',
            'sub_fields' => array(
                array( 'key' => 'field_cta_text', 'label' => 'ボタンテキスト', 'name' => 'text', 'type' => 'text' ),
                array( 'key' => 'field_cta_link', 'label' => 'リンク',         'name' => 'link', 'type' => 'url' ),
            ),
        ),
    ),
),
```

### テンプレート側

ACF 標準の `have_rows()` / `the_sub_field()` / `get_row_layout()` がそのまま使えます:

```php
<?php if ( have_rows( 'features' ) ) : ?>
    <ul>
    <?php while ( have_rows( 'features' ) ) : the_row(); ?>
        <li>
            <strong><?php the_sub_field( 'title' ); ?></strong>
            <p><?php the_sub_field( 'body' ); ?></p>
        </li>
    <?php endwhile; ?>
    </ul>
<?php endif; ?>

<?php if ( have_rows( 'sections' ) ) : while ( have_rows( 'sections' ) ) : the_row(); ?>
    <?php if ( get_row_layout() === 'hero' ) : ?>
        <section class="hero">
            <h1><?php the_sub_field( 'title' ); ?></h1>
            <?php $img = get_sub_field( 'image' ); ?>
            <?php if ( $img ) : ?><img src="<?php echo esc_url( $img['url'] ); ?>" alt=""><?php endif; ?>
        </section>
    <?php elseif ( get_row_layout() === 'cta' ) : ?>
        <a class="cta" href="<?php the_sub_field( 'link' ); ?>"><?php the_sub_field( 'text' ); ?></a>
    <?php endif; ?>
<?php endwhile; endif; ?>
```

## サポートされているサブフィールドタイプ

text / textarea / url / number / image

> 画像は `return_format` (`array` / `url` / `id`)、テキストエリアは `new_lines` (`wpautop` / `br` / なし) を指定可。

## ロードマップ

| 内容 | 状態 |
| --- | --- |
| プラグイン骨格 + ACF 依存チェック | ✅ |
| Repeater フィールド | ✅ |
| Flexible Content フィールド | ✅ |
| サブフィールド: text / textarea / url / number / image | ✅ |
| 行・レイアウトのドラッグ並べ替え | ✅ (v0.5.2) |
| サブフィールド追加: select / wysiwyg / file | ⏳ 次の優先 |
| Repeater に ↑↓ ボタン (キーボード操作) | ⏳ |
| Options Page | ⏳ |
| Clone フィールド | ⏳ |

## 動作要件

- WordPress 5.8 以上
- PHP 7.4 以上
- Advanced Custom Fields (無料版 / Pro どちらでも)

## ライセンス

GPL-2.0-or-later

## 作者

[starcraft-n](https://starcraft-n.co.jp)
