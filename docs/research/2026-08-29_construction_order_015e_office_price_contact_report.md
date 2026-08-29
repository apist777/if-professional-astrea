# Construction Order 015E — Visual v2: Office / Office Hours / Price / Contact — 施工報告

- **Status**: IMPLEMENTATION COMPLETE
- **Date**: 2026-08-29
- **担当**: クロエ (Chloe)
- **承認元Order**: Construction Order 015E（015C/015Dは公式承認済み前提）
- **Functional Baseline**: RC1 (1.0.0-rc1) — Version番号は変更していない

## 1. 施工範囲

Office Page（事務所名・所在地・電話番号・営業時間・SNS）、Price Page、Contact Page（Contact Formブロック）のVisual Presentation。目的：「事務所情報を確認する」「営業時間を確認する」「料金を見る」「問い合わせる」という実際の利用行動を、見た目と情報階層の両方で支援すること。

Architecture Freeze（Office/Price/Contact Data Schema・CPT・Setup Architecture・SEO Architecture・Mail Architecture・Retention/Deletion Architecture・FREE/PRO境界）は遵守。新規Semantic Data/Postmetaは追加していない。

## 2. Pre-construction Audit（要旨）

施工前にCode/実画面を確認し、以下を把握した：

- Office Profile（`office-profile.php`）は`office_name`/`address`/`phone`/`business_hours.{weekly,exceptions}`/`sns_links`を保持し、`get_office_profile()`が唯一の公開読み取り境界。
- Office Name/Address/PhoneはこれまでBlock Bindings付きの3つの独立した`core/paragraph`（`theme/patterns/office-info.php`、及び`core/includes/setup-pages.php`の`page_definitions()`に同一構造がハードコード）のみで表示されており、Labelが一切無く、値が空の場合は空の`<p></p>`が残る既知の制約があった（`office-hours-block.php`のDocblockが既に指摘していたBlock Bindingsの構造的限界と同一）。
- `astrea/office-hours`・`astrea/office-sns`はConstruction Order 011で既に`<dl>`/`<ul>`のSemanticなDynamic Blockとして実装済みで、Visual Styleが未着手なだけだった。
- `astrea/price-list`はGroup Kicker/Name/Amount/NotesのMarkup順序が既に確立済み（Construction Order 015C）。HOME Teaser（`heading`属性あり）と専用Price Page（`heading`属性なし、`emptyMessage`のみ）は同じBlockを共有。
- Contact Formブロック（`contact-form-block.php`）は既にLabel/Input紐付け・`aria-required`/`aria-invalid`/`aria-describedby`・`role="alert"`のError通知・`role="status"`のSuccess State・Nonce/Honeypot/Rate Limitを備えた成熟したMarkupで、Visual Style以外に手を加える必要はなかった。

## 3. Office Page v2

### 3.1 新設: `astrea/office-summary` Dynamic Block

`core/includes/office-summary-block.php`（新規）。`get_office_profile()`から`office_name`/`address`/`phone`を読み、Office Nameは単独のIdentity Text（Labelなし）、Address/Phoneは`<dl>`のLabel/Value行として表示する。個別Fieldが空ならその行だけを省略し、全て空ならBlock自体が空文字列を返す（`heading`/`emptyMessage`は既存Dynamic Block群と同じ規約）。

`theme/patterns/office-info.php`と`core/includes/setup-pages.php`の`page_definitions()`（'about'定義）の両方を、旧・3 Paragraph構成からこの新Blockへ置き換えた。**既存のSetup生成済みPageは書き換えていない**（`generate_pages()`は初回生成時のみ内容を書き込む既存の仕組みのまま）。Owner Fixtureの「事務所概要」ページのみ、Visual Verification目的で新Block構成へ手動更新した（詳細は§9）。

### 3.2 レイアウト上の発見：`layout:"constrained"`と`display:flex`の衝突

Office Summary/Hours/SNSを1つのGroup（`className:"astrea-office-page"`）にまとめる際、当初`layout:{"type":"constrained"}`＋独自CSSの`display:flex`を組み合わせたところ、Office Hoursの`<dl>`が横幅約212pxまで縮んで中央寄せされる不具合が発生した。原因はGutenberg core自身のグローバルルール`:where(.is-layout-constrained) > :where(...)`が全ての直接の子要素へ`margin-left/right:auto !important`を強制しており、Flexboxの仕様上「Cross軸のMarginがautoだと`align-items:stretch`が無効化される」ため。

修正：GroupのLayoutをGutenberg純正の「Flex（垂直）」（`layout:{"type":"flex","orientation":"vertical"}`）へ変更し、CSS側は`.astrea-office-page.is-layout-flex{align-items:stretch;}`のみを追加（Gutenberg生成の`align-items:flex-start`をClass 2つ分の詳細度で上書き）。独自の`display:flex`宣言は不要になった。

### 3.3 Office Hours v2

`office-hours-block.php`の週次`<dl>`をCSS Gridで「曜日｜時間」の2列Table状に整形。休業日の`<dd>`にPHP側で`is-closed`Class（後方互換な追加のみ）を付け、Secondary色（Trustではグレー、Naturalでは暖色系のSecondary）へ変更——赤・警告色・Iconは使用していない。Closure Exceptions（既存の`business_hours.exceptions`）は週次Tableの下に、Base背景＋左Borderの独立したBoxとして表示し、通常営業と区別できるようにした。

### 3.4 Office SNS v2

`office-sns-block.php`自体は無変更。CSSのみでBorder付きPill形状のChipへ変換し、`::after{content:"↗"}`（装飾Unicode文字1つ）でExternal Link感を付与した。新規Icon Library・Brand Logoは使用していない。

## 4. Price Page v2

`astrea/price-list`に新しいContext属性は追加せず、既存の`heading`属性（HOME Teaserのみが設定し、Price Page/Patternは設定しない）を再利用して、`heading`が設定されている場合のみ`wp-block-astrea-price-list--compact`Classを付与するよう`price-list-block.php`を変更した。

- **HOME Teaser（`--compact`）**：既存の見た目を完全維持（詰まったPadding、地味なGroup Kicker）。
- **Price Page（Classなし＝新Default）**：Paddingを拡張し、Group Kickerをsurface背景のPill状Badgeへ強調——Group Bucket化・再ソート（Post v1 Finding 8）には一切踏み込んでいない。

## 5. Contact Page v2

`contact-form-block.php`のPHP/Markupは**無変更**（既存Class・既存Accessibility属性をそのまま利用）。`.wp-block-astrea-contact-form`（Form自体にもSuccess Stateにも共通して付く既存Class）へCard Style（Surface背景・Border・radius-md・`max-width:32rem`・中央寄せ）を適用。Input/Textareaを`box-sizing:border-box`＋統一Padding/min-heightでCSSのみ統一し、`outline:2px solid var(--wp--preset--color--primary);outline-offset:2px`のFocus Visibleを明示追加（従来はBrowser Default Outlineのみで、Themeとしては未定義だった。`outline:none`は使用していない）。Error表示は既存の`role="alert"`枠・`aria-invalid`・`.astrea-contact-form__field-error`をCSSのみで強調。Submit ButtonはMobileで全幅、480px以上で自動幅に戻す。

## 6. 検証結果

- **Contact Reachability**: Single Closing CTA →「お問い合わせはこちら」クリック → Contact Pageへの遷移を実機確認。015Dの`get_contact_page_url()` Fallbackに回帰なし。
- **Error State**: 全項目未入力で送信 → 上部に`role="alert"`の要約通知、各項目下に個別Error文言、該当Inputの枠線が強調される状態を確認。
- **Success State**: 実際にForm送信（テスト用の一時Inquiry、確認後`wp post delete --force`で削除済み）→ Cardスタイルの完了メッセージを確認。実メール送信の外部副作用は検証していない（Order指示通り）。
- **Empty State**: Owner FixtureのSNSリンク・Closure Exceptionsは元々未設定だったため、Visual検証のため実データを追記した（§9参照）。
- **Long Japanese Stress**: Office Name/Address/Closure Exception Label、Price Name/Amountを一時的に長い文字列へ差し替えて確認（検証後に元の値へ復元済み）——ellipsis/line-clamp/truncateなし、横スクロールなしを確認。
- **Responsive**: 320/375/768/1440pxで`scrollWidth`自動Overflow検査、Office/Price/Contactの全ページでOverflow 0件。
- **Core OFF**: astrea-core無効化状態でOffice/Price/Contactの3ページとも200・Fatalなしを確認、直後に再有効化。
- **Style Variation**: Trust（Default）に加えNatural・Modernで Office/Contact を確認、いずれもCard/Table/Chip/Focusが正しく機能。最終状態はTrustへ復元済み。
- **Focus**: `#astrea_contact_name`と`#astrea_contact_message`の実際のComputed Styleを取得し、`outline: 2px solid rgb(31,58,92)`（Primary色）が適用されていることを確認。
- **Block Validation**: Office/Price/ContactのPage Editorを開き、Invalid Content警告 0件を確認（3ページとも）。
- **Theme Check**: REQUIRED/WARNING 0件（INFO 1件のみ、RC1から継続の既知許容事項）。
- **PHPUnit**: 359 tests / 560 assertions、OK（既存Baselineと一致、回帰なし）。
- **PHPCS**: 65/65（0 errors, 0 warnings）。

## 7. Known Issues / Post v1 Backlog

- CPT Archiveの`og:url`がHOME URLを返す既存挙動（015D発見）は今回も対応していない。`06_astrea_visual_v2_design_system.md`の「Release前Backlog」に明示的に記録した。
- Price Group Finding 8（Bucket化・再ソート）は引き続き着手していない（Order §18で明示禁止）。
- Contact FormのPrivacy/Consent表示は既存のまま変更していない（本Owner FixtureはPrivacy Consentが不要な設定のため、Consent行のVisual確認は今回未実施）。

## 8. Visual Score（自己評価、甘く採点しない）

| 対象 | Install-state相当の評価 |
|---|---|
| Office | 84 |
| Price | 83 |
| Contact | 85 |
| Whole-site | 84 |

Officeは営業時間Table化とLabel/Value構造化で「メモ帳」感を脱したが、Owner Fixtureに実写真が無いためVisualなAnchorが文字情報のみである点を減点材料とした。Priceは階層は明確になったが、Amountの強調が015C比でほぼ変わっていない点を保守的に評価。ContactはCard化・Focus可視化・Error/Success State整備で「入力欄」から「対話の場所」へ近づいたと判断する。

## 9. Owner Fixtureへの変更（製品コードではない）

Visual Verification専用のOwner Fixtureに対し、以下を実施した（いずれも「実在するはずだが未入力だった」データの追記、または新Block構成への更新であり、smoke-test.shは一切実行していない）：

- 「事務所概要」ページ（Page ID 1915）の投稿本文を、旧・3 Paragraph構成から新しい`astrea/office-summary`＋既存`astrea/office-hours`/`astrea/office-sns`のGroup構成へ更新（オーナー自身が書いた紹介文の段落はそのまま維持）。
- Office ProfileのSNSリンク2件（X (Twitter)・note）と、年末年始休業のClosure Exceptionを追加。
- Long Japanese Stress検証のためPrice/Office Profileの一部値を一時的に長い文字列へ差し替え、検証後に元の値へ完全復元（`wp option get`でのBackup/Restoreを実施）。
- Contact Form成功状態検証で作成した一時Inquiry投稿は確認後に完全削除。

## 10. 変更ファイル一覧

- `core/includes/office-summary-block.php`（新規）
- `core/includes/office-hours-block.php`（`is-closed` Class追加）
- `core/includes/price-list-block.php`（`--compact` Class追加）
- `core/includes/setup-pages.php`（'about'定義のBlock構成更新）
- `core/astrea-core.php`（新規ファイルのrequire追加）
- `core/assets/js/editor-blocks.js`（`astrea/office-summary`のEditor登録）
- `theme/patterns/office-info.php`（新Block構成へ更新）
- `theme/theme.json`（Office Summary/Hours/SNS/Price/Contact FormのCSS追加）
