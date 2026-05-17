=== SCN ACF Extra ===
Contributors: starcraftn
Tags: acf, advanced-custom-fields, repeater, custom-fields
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ACF 無料版に Repeater など Pro 相当のフィールドを追加する拡張プラグイン。Pro 互換のメタ保存形式で、後から ACF Pro へ無断データ移行可能。

== Description ==

starcraft-n が制作・運用する WordPress 案件のために作られた、Advanced Custom Fields (ACF) 無料版の拡張プラグインです。Repeater フィールド (将来的に Flexible Content・Options Page・Clone も予定) を、ACF Pro と完全互換のメタ保存形式で実装しています。

= 特徴 =

* **ACF Pro と完全互換のメタ保存形式** — 後から ACF Pro を有効化しても、保存済みデータはそのまま使用可能。
* **PHP コードからのフィールド登録に最適化** — `acf_add_local_field_group()` ベースの運用を想定。
* **軽量** — Pro の機能をすべて再実装するのではなく、starcraft-n の実案件で実際に必要な範囲のみカバー。

= 必要環境 =

* WordPress 5.8 以上
* PHP 7.4 以上
* Advanced Custom Fields (無料版または Pro) が有効化されていること

== Installation ==

1. プラグインを `/wp-content/plugins/scn-acf-extra/` にアップロードします。
2. WordPress 管理画面の「プラグイン」メニューから有効化します。
3. ACF (無料版) が有効化されていることを確認してください。

== Changelog ==

= 0.1.0 =
* Initial release: Repeater field (text sub-field, add/remove rows).
