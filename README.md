# SCN ACF Extra

ACF 無料版に Repeater など Pro 相当のフィールドを追加する拡張プラグイン。**ACF Pro と完全互換のメタ保存形式**で、後から Pro 有効化してもデータはそのまま動きます。

## ステータス

`v0.1.0` — Repeater MVP (テキスト sub-field、追加・削除のみ)

## ロードマップ

| 段階 | 内容 | 状態 |
| --- | --- | --- |
| 1 | プラグイン骨格 + ACF 依存チェック | ✅ |
| 2 | Repeater フィールド (テキスト sub-field) | ✅ |
| 3 | sub-field 種類拡張 (image / select / wysiwyg など) | ⏳ |
| 4 | ドラッグ並び替え・最小/最大行数・collapsed UI | ⏳ |
| 5 | Flexible Content | ⏳ |
| 6 | Options Page | ⏳ |
| 7 | Clone | ⏳ |

## 使い方 (v0.1.0)

`functions.php` などで `acf_add_local_field_group()` を使ってフィールドを登録します。Pro と同じ書式です。

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
                'type'         => 'scn_repeater',
                'min'          => 0,
                'max'          => 5,
                'button_label' => 'ポイントを追加',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_features_title',
                        'label' => 'タイトル',
                        'name'  => 'title',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_features_body',
                        'label' => '内容',
                        'name'  => 'body',
                        'type'  => 'text',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'works',
                ),
            ),
        ),
    ) );
} );
```

テンプレート側:

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
```

## ライセンス

GPL-2.0-or-later

## 作者

[starcraft-n](https://starcraft-n.co.jp)
