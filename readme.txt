=== SC ACF Extra ===
Contributors: starcraftn
Tags: acf, advanced-custom-fields, repeater, custom-fields
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.6.0
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

1. プラグインを `/wp-content/plugins/sc-acf-extra/` にアップロードします。
2. WordPress 管理画面の「プラグイン」メニューから有効化します。
3. ACF (無料版) が有効化されていることを確認してください。

== Changelog ==

= 0.6.0 =
* Feat: GitHub Release ベースの自動アップデートに対応。`YahnisElsts/plugin-update-checker` v5.6 を `lib/plugin-update-checker/` に同梱し、`PucFactory::buildUpdateChecker()` でリポジトリを登録。今後 `vX.Y.Z` タグでリリースを切ると wp-admin の「更新通知」から差分適用できる。
* Note: Release アセット (zip) を有効化 (`enableReleaseAssets()`) しているため、GitHub Release に手動でビルド zip を添付した場合はそちらが優先される。添付が無い場合は tag からの自動 zipball にフォールバック。

= 0.5.2 =
* Feat: 行 / レイアウトのドラッグ並べ替えに対応 (Repeater は ≡ ハンドル、Flexible Content はヘッダー左の ≡ ハンドル)。jQuery UI sortable ベース。Flexible の ↑↓ ボタンはアクセシビリティのため併存。
* Fix: 行を並べ替えた直後に「更新」を押しても保存が走らなかった不具合を修正。原因は reindex 処理が `name` 属性のみ書き換えており、ACF がクライアント側 validation で参照する `id` / `label[for]` がずれてサブミットが silent に中断されていたため。`id` 属性は ACF が大括弧をハイフン化する形式 (`acf-field_xxx-0-…`) で書き換えるよう修正。
* Fix: ドラッグ中の helper 要素 (clone) から `id` 属性を除去し、duplicate-id 状態で wp.media / select2 等が helper にバインドされる問題を回避。
* Defensive: `update_value()` の入口で配列内の非配列要素を `array_filter( ..., 'is_array' )` で除去し、想定外のクライアント送信時にも壊れた行が書き込まれないように。
* 既知の制限: Repeater には ↑↓ ボタンが無いためキーボードのみで並べ替えできません (Flexible は併存)。次バージョンで対応予定。

= 0.5.1 =
* Fix: × ボタンで行を全削除しても、保存後に行が復活してしまう不具合を修正 (Repeater / Flexible Content 共通)。原因は 0 行送信時に `$_POST['acf'][<field_key>]` 自体が消え、`update_value()` が呼ばれず親メタが古いまま残るため。ACF Pro と同様の `acfcloneindex` 隠しフィールドを wrapper 直下に出力するように変更。

= 0.1.0 =
* Initial release: Repeater field (text sub-field, add/remove rows).
