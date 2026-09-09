# Construction 024 — ASTREA LIVE DEMO Production Migration

demo.project-if.jp/astrea/ 本番移植施工

- Modifier: Chloe
- Phase A Start: 2026-09-10 02:12:12 JST（実測）
- Phase A End: 2026-09-10 02:14:37 JST（実測）
- Phase A Duration: 0:02:25
- Product baseline: ASTREA Theme 1.0.2 / ASTREA Core 1.0.1（**無改変**）

---

## PHASE A — Infrastructure Preflight / Script Audit / Preparation

### 1. Current Infrastructure Preflight（§3）— read-only、eureka権限のみ

`ssh eureka@162.43.71.160`経由で実測（sudo不使用）:

- `/var/www/demo.project-if.jp/astrea` — **存在しない**（`ls`が「そのようなファイルやディレクトリはありません」）
- `/var/www/if-thema-astrea-demo` — **存在しない**
- `/etc/nginx/sites-available/demo.project-if.jp`にastrea関連の記述 — **存在しない**

**→ `/astrea/`の衝突なし。Order §3の必須条件を満たし、続行可能と判断した。**

現在のnginx vhost全文（実測、40行）:

```nginx
server {
    server_name demo.project-if.jp;
    client_max_body_size 20M;
    root /var/www/demo.project-if.jp;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # BEGIN if-thema-demo-subpath: modern
    location /modern/ {
        try_files $uri $uri/ /modern/index.php?$args;
    }
    # END if-thema-demo-subpath: modern
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }

    listen [::]:443 ssl; # managed by Certbot
    ... (SSL/Certbot lines, unchanged)
}
server {
    ... (HTTP→HTTPS redirect block, unchanged)
}
```

`/modern/`のASTREA同居はなく、Modernブロックのみが存在することを確認。

### 2. Modern Reference Architecture（§4）— 実測値

| 項目 | 実測値 |
| --- | --- |
| Public URL | `https://demo.project-if.jp/modern/` |
| Modern docroot | `/var/www/if-thema-modern-demo`（所有 `eureka:www-data`、mode `2775`＝`drwxrwsr-x`） |
| Modern symlink | `/var/www/demo.project-if.jp/modern -> /var/www/if-thema-modern-demo` |
| nginx marker | `# BEGIN if-thema-demo-subpath: modern` 〜 `# END if-thema-demo-subpath: modern`、`location /modern/ { try_files $uri $uri/ /modern/index.php?$args; }` |

DB名（`if_thema_modern_demo`）・DBユーザー（`if_thema_modern`）はMySQL直接アクセス権限がeurekaにないため実測できないが、bootstrapスクリプトのソースコード自体（後述）から導出される命名パターンであり、docroot/symlink/nginxの実測一致（すべてスクリプトの命名規則通り）から、DB側も同一パターンで作成されたと判断できる。Modern環境自体には一切変更を加えていない。

### 3. Bootstrap Script Re-Audit（§5）— 重大な発見

`~/project-if/scripts/ops/demo-product-subpath-bootstrap.sh`をソースコードから確認:

- 現在のリポジトリ版は、Modern施工時に発生した`awk -v anchor="..."`のエスケープ処理バグを**修正済み**（`grep -Fxn` + `head`/`tail`による行番号ベース挿入。コード内に修正理由のコメントあり）であることをコードで確認した。

**しかし、VPS上の`/home/eureka/demo-product-subpath-bootstrap.sh`（Modern施工時に転送された既存コピー）のSHA256を確認したところ、`3f8ef262...`——リポジトリの現在の修正版（SHA256 `a5df0736...`）とは一致せず、Modern施工時の旧・バグ版のままだった。**

これは「既存スクリプトを無条件に信用しない」という本Order §5自体の警告が的中したケースである。もしこの点を確認せず旧コピーをそのまま実行していた場合、Modernで発生したのと同じnginx挿入の無言失敗を再現していた可能性が高い。

**対応**: 現在の修正版`demo-product-subpath-bootstrap.sh`と`demo-product-subpath-rollback.sh`（VPS上に未転送だった）を、`eureka`権限（root不要）でVPSへ再転送し、転送後にSHA256を再照合して一致を確認した。

| ファイル | ローカルSHA256 | 転送後リモートSHA256 | 一致 |
| --- | --- | --- | --- |
| `demo-product-subpath-bootstrap.sh` | `a5df0736edfad787ff59597dd614e8f0f448f877e19ebd4998d703655e62b3f0` | 同上 | ✅ |
| `demo-product-subpath-rollback.sh` | `99f2d63f4f6ebdb8e3110743d780f524e3a7da99c57407c36366cec478af433e` | 同上 | ✅ |

転送先: `/home/eureka/demo-product-subpath-bootstrap.sh`（mode 700, owner eureka:eureka）、`/home/eureka/demo-product-subpath-rollback.sh`（同）。いずれもeureka自身のホーム領域への書き込みであり、root権限は不要（scp転送のみ、sudo不使用）。

### 4. Required Script Postconditions（§6）

現在の修正版スクリプトの設計を確認した結果:

- Pre-flight時点で「nginx location `/${SLUG}/`が既に存在」「MARKER_BEGINが既に存在」を1回のgrep -Fで検出し、いずれかがヒットすればSTOP（無変更）→ **同一slugでの二重挿入は構造的に発生しない**
- アンカー行（`location ~ \.php$ {`）の出現回数を事前に`grep -Fxc`でカウントし、1でなければSTOP（無変更）→ **挿入位置の曖昧さを排除**
- 挿入は`grep -Fxn`で求めた行番号への`head`/`tail`ベースの単純結合のみ（正規表現解釈なし）→ **BEGIN/END各1回、意図した`location ~ \.php$`の直前に確実に挿入される設計**
- `nginx -t`成功時のみ`systemctl reload`、失敗時はbackupから自動復元し**reloadしない**

このスクリプト自体は「exit 0 / nginx -t PASS」以上の構造的保証（衝突検出・アンカー数検証・原子的挿入）を備えているが、挿入**後**に自己診断で「marker/locationが正確に1回ずつ存在する」ことを明示的に再確認する行は含んでいない（Modern施工時に作成した是正スクリプト`demo-product-subpath-nginx-apply.sh`にはこの自己診断ステップがある）。

**このOrder自身が要求するポストコンディション確認（§6の1〜7）は、Owner実行直後にクロミちゃんが実施するread-only verification（Phase B / §15）で満たす設計とする**——本Order自身がPhase A（準備）→Owner施工→Phase B（read-only検証）という二段階構造を採っているため、この構造とも整合する。スクリプト自体の修正は不要と判断した（Bではない）。

### 5. Idempotency / Collision Safety（§7）

pre-flight確認済み（§4の5項目、上記4参照）。「既にあるからそのまま使う」という自動判断は存在せず、いずれの衝突もSTOPする設計であることをコードで確認した。

### 6. Rollback Audit（§8）

`demo-product-subpath-rollback.sh`をソースコードから確認:

- 全操作が`${SLUG}`（今回は`astrea`）でパラメータ化されており、対象はnginxの当該markerブロック・当該symlink・当該docroot（`/var/www/if-thema-astrea-demo`のみ）・当該DB/ユーザー（`if_thema_astrea_demo`/`if_thema_astrea`のみ）・当該credentialファイルのみ
- `rm -rf`は`${NEW_DOCROOT}`という完全修飾・slug固有のパスにのみ適用（広範なワイルドカード削除は存在しない）
- `DROP DATABASE IF EXISTS`も完全修飾のDB名のみ（ワイルドカードなし）
- nginx側もmarker間の`sed`範囲削除のみ、backup→`nginx -t`→pass時のみreload、fail時は復元、という同じ安全設計

**危険な広範操作は確認されなかった。**

### 7. ASTREA Parameter Plan（§9）— スクリプトの実際の命名規則から導出（推測なし）

| パラメータ | 値 | 根拠 |
| --- | --- | --- |
| slug | `astrea` | Order指定 |
| public path | `https://demo.project-if.jp/astrea/` | slugから機械的に導出 |
| docroot | `/var/www/if-thema-astrea-demo` | スクリプト内`NEW_DOCROOT="/var/www/if-thema-${SLUG}-demo"`、Modernの実測値`if-thema-modern-demo`と同一パターン |
| symlink | `/var/www/demo.project-if.jp/astrea -> /var/www/if-thema-astrea-demo` | `SYMLINK_PATH="${EXISTING_DOCROOT}/${SLUG}"`、Modern実測値と同一パターン |
| DB名 | `if_thema_astrea_demo` | `DB_NAME="if_thema_${SLUG}_demo"` |
| DBユーザー | `if_thema_astrea` | `DB_USER="if_thema_${SLUG}"` |
| credentialファイル | `/home/eureka/.if-thema-astrea-demo.cred` | `CRED_FILE="/home/eureka/.if-thema-${SLUG}-demo.cred"` |

### 8. Migration Package Verify（§10）

`docs/demo-assets/yamada-live-demo/`（Construction 023-B時点、commit `371aace`）の内容を再確認:

- `yamada-demo-export.wxr`（32 items、正式画像組み込み後の状態）
- `images/astrea-demo-yamada-professional-portrait.png`（1616×973、1,725,005 bytes）
- `images/astrea-demo-yamada-case-01-construction-permit.png`（1774×887、1,812,903 bytes）
- `images/web/`配下に最適化JPEG2点（148,046 / 176,253 bytes）
- `scripts/`配下に構築・組み込みスクリプト一式
- placeholderへ戻る依存は確認されなかった（`build-content.php`は初期構築用、`integrate-final-images.php`が最終画像への切り替えを担当し、両者を順に実行すれば必ず正式画像で終わる設計）

### 9. Release Artifact Verify（§11）— 匿名再検証

| Artifact | Size | SHA-256（今回再取得） |
| --- | --- | --- |
| astrea-theme-1.0.2.zip | 162,246 bytes | `72cd1fc412b0d102e33f28d0437fd31fac7f2846b0e17725ea7345655fd5c176` |
| astrea-core-1.0.1.zip | 160,065 bytes | `88a526527ebbfdbe85aacc3c4311b35ef3c622df1e6bb1292a86cc0028839531` |

Construction 023-Bで使用した値と完全一致。GitHub公式Releaseから匿名`curl`で再取得し確認済み。ローカルsource treeからの独自ビルドは行っていない。

### 10. PHASE A VERDICT（§12）

**A. INFRASTRUCTURE PREFLIGHT PASS**

（ただし、上記§3の通り「B相当」の実質的な発見——VPS上の既存bootstrapスクリプトコピーが旧・バグ版だった——があり、Theme/Core本体には触れない範囲でスクリプトファイルの再転送のみ実施し是正済み。）

---

## PHASE B — OWNER EXECUTION GATE（§13）

**クロミちゃんはこの先、sudoを要する操作を一切行いません。**

以下は、上記で監査・是正済みのスクリプト（VPS上のSHA256を確認済み）に基づく、Ownerが実行する正確なコマンドです。

```
ssh -p 10428 eureka@162.43.71.160
sudo bash /home/eureka/demo-product-subpath-bootstrap.sh astrea
```

（先に内容だけ確認したい場合は末尾に `--dry-run` を付けてください。実行時はプランが表示され、`yes`と入力するまで何も変更されません。）

このコマンドは:

- `/astrea/`用の新規docroot（`/var/www/if-thema-astrea-demo`）を作成
- symlink（`/var/www/demo.project-if.jp/astrea`）を作成
- nginx vhostへ`# BEGIN/END if-thema-demo-subpath: astrea`ブロックを追記（`/modern/`ブロックや既存rootには一切触れません）
- `nginx -t`検証後、成功時のみreload
- MySQL DB（`if_thema_astrea_demo`）・ユーザー（`if_thema_astrea`）を新規作成
- credentialを`/home/eureka/.if-thema-astrea-demo.cred`（mode 600）へ書き込み

実行後、**stdout・stderrの全文、およびexit statusをそのままお知らせください。**（DB/sudoパスワードは共有不要です。）

---

**AWAITING OWNER PRIVILEGED EXECUTION.**

Ownerの実行結果を受け取るまで、Phase B（WordPress導入・検証・公開）へは進みません。
