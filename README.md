<div align="center">

# 🔒 Mesterjelszó

**Védd egyetlen jelszóval a teljes WordPress weboldalt — oldalak, bejegyzések, egyedi tartalomtípusok, a REST API és a bejelentkezési felület.**

[![Version](https://img.shields.io/badge/version-1.0.2-6c5ce7?style=for-the-badge)](changelog.txt)
[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759b?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org)
[![Tested](https://img.shields.io/badge/tested%20up%20to-7.0.1-46b450?style=for-the-badge)](changelog.txt)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/license-GPLv2%2B-blue?style=for-the-badge)](https://www.gnu.org/licenses/gpl-2.0.html)

[Funkciók](#-funkciók) •
[Telepítés](#-telepítés) •
[Beállítások](#%EF%B8%8F-beállítások-áttekintése) •
[GYIK](#-gyik) •
[Biztonság](#-biztonsági-modell) •
[Changelog](#-changelog)

</div>

---

## 📖 Miről szól?

A **Mesterjelszó** egy önállóan fejlesztett, saját kódbázisú WordPress bővítmény, amely egyetlen, admin által beállított jelszóval zárolja a teljes weboldalt a nyilvánosság elől — ideális fejlesztés alatt álló, ügyfélnek bemutatandó, vagy éppen ideiglenesen zárolandó weboldalakhoz.

Amíg a látogató nem adja meg a helyes jelszót, semmilyen tartalomhoz (oldalak, bejegyzések, egyedi tartalomtípusok), a WordPress REST API-hoz vagy a bejelentkezési felülethez nem fér hozzá — miközben a Jetpack és hasonló, REST API-ra/XML-RPC-re támaszkodó szolgáltatások továbbra is zavartalanul működnek.

<br>

## ✨ Funkciók

<table>
<tr>
<td width="50%" valign="top">

### 🎨 Admin felület
- Modern, kártya-alapú, reszponzív dizájn, 4 fület (Alapbeállítások / Megjelenés / Biztonság / Napló)
- **Élő előnézet** panel — azonnal látod, mit lát majd a látogató
- Feltölthető logó, automatikusan megjelenő weboldal-név
- Szabadon szerkeszthető, korlátozott HTML-t támogató üzenet
- Háttér: szín vagy kép, állítható átlátszóság
- Világos / sötét / automatikus megjelenési mód
- Egyedi kiemelő- és szövegszín

</td>
<td width="50%" valign="top">

### 🔐 Biztonság
- Mesterjelszó egyirányú (WordPress core szabvány szerinti) hash-elve tárolva
- Admin felületen **megtekinthető** a jelenlegi jelszó (csapat-koordinációhoz)
- Brute-force védelem: próbálkozás-limit + ideiglenes zárolás
- Biztonságos, HttpOnly, SameSite sütire épülő munkamenet
- Opcionális **"Emlékezz rám"** (alapból kikapcsolva, 15 nap)
- **Megbízható IP-címek** listája (CIDR-támogatással)
- Nonce-védelem, input validáció, output escaping mindenhol

</td>
</tr>
<tr>
<td width="50%" valign="top">

### 🔌 Kompatibilitás
- Automatikus **Jetpack**-kompatibilitás (REST API kivétel-lista + XML-RPC mentesítés)
- Testreszabható REST API route-kivételek
- Testreszabható AJAX végpont-kivételek (pl. nagy fájlfeltöltőkhöz)
- HTTP 200 alapértelmezett válasz a jelszókérő oldalon (nincs tárhelyi/CDN hibaoldal-ütközés)

</td>
<td width="50%" valign="top">

### 📊 Naplózás
- **Bejelentkezési napló**: sikertelen próbálkozások IP-cím + ország/város szerint
- Összesítők: 1 nap / 1 hét / 1 hónap / 1 év bontásban
- Automatikus 1 éves megőrzési idő, kézi törlési lehetőség
- Késleltetett, gyorsítótárazott geolokáció (nem lassítja a látogatót)

</td>
</tr>
</table>

<br>

## 🖼️ Előnézet

<div align="center">

| Admin felület | Jelszókérő felület |
|:---:|:---:|
| Kártya-alapú beállítások élő előnézettel | Középre igazított, nagy betűs, akadálymentes |

*(Screenshot-ok ide kerülhetnek — `assets/screenshot-1.png`, `assets/screenshot-2.png`)*

</div>

<br>

## 🚀 Telepítés

<details open>
<summary><strong>1. módszer — ZIP feltöltés (ajánlott)</strong></summary>

1. Töltsd le a legfrissebb kiadást (`mesterjelszo.zip`)
2. WordPress admin → **Bővítmények → Új hozzáadása → Bővítmény feltöltése**
3. Válaszd ki a ZIP fájlt, majd kattints a **Telepítés most** gombra
4. Aktiváld a bővítményt

</details>

<details>
<summary><strong>2. módszer — FTP / kézi telepítés</strong></summary>

```bash
# Klónozd vagy másold a mesterjelszo mappát a plugins könyvtárba
cp -r mesterjelszo /var/www/html/wp-content/plugins/
```

Ezután aktiváld a **Bővítmények** menüpontban.

</details>

<details>
<summary><strong>3. módszer — WP-CLI</strong></summary>

```bash
wp plugin install mesterjelszo.zip --activate
```

</details>

<br>

### Gyorsindítás

```
1. Aktiválás után navigálj a "Mesterjelszó" admin menüponthoz
2. Alapbeállítások fül → állítsd be a mesterjelszót → Beállítások mentése
3. Kapcsold be a "Weboldal védelmének bekapcsolása" kapcsolót
4. Kész — a weboldal mostantól jelszóval védett
```

> [!TIP]
> Bejelentkezett adminisztrátorként a `wp-admin` irányítópult **mindig** elérhető marad, így soha nem zárhatod ki saját magad.

<br>

## ⚙️ Beállítások áttekintése

<details>
<summary><strong>📁 Alapbeállítások fül</strong></summary>
<br>

| Beállítás | Alapérték | Leírás |
|---|---|---|
| Védelem bekapcsolása | ✅ Be | Fő ki/be kapcsoló |
| Mesterjelszó | *(nincs)* | Egyirányú hash-elve tárolva |
| Jelenlegi jelszó megtekintése | — | Csak admin, AJAX + nonce védett |
| Adminisztrátorok mentesítése | ✅ Be | Bejelentkezett adminok átugorják a zárat |

</details>

<details>
<summary><strong>🎨 Megjelenés fül</strong></summary>
<br>

| Beállítás | Alapérték | Leírás |
|---|---|---|
| Logó | *(nincs)* | Média-feltöltőn keresztül |
| Weboldal nevének megjelenítése | ✅ Be | `Beállítások → Általános` alapján |
| Üzenet | *(alap szöveg)* | Korlátozott HTML engedélyezett |
| Háttér típusa | Szín | Szín vagy kép |
| Háttér átlátszósága | 100% | 0–100% |
| Megjelenési mód | Sötét | Világos / Sötét / Automatikus |

</details>

<details>
<summary><strong>🔐 Biztonság fül</strong></summary>
<br>

| Beállítás | Alapérték | Leírás |
|---|---|---|
| Munkamenet hossza | 24 óra | 1–720 óra |
| "Emlékezz rám" | ❌ Ki | Ha be, 1–365 nap (alap: 15) |
| Próbálkozás-limit | 5 | Ennyi után zárolás |
| Zárolás időtartama | 15 perc | 1–1440 perc |
| REST API kivételek | `jetpack/v4`, `jetpack-blogs/1.1` | Soronkénti route-prefix lista |
| AJAX végpont kivételek | *(üres)* | Más bővítmények kompatibilitásához |
| 503 HTTP állapotkód | ❌ Ki | ⚠️ Lásd [Biztonsági modell](#-biztonsági-modell) |
| Megbízható IP-címek | ❌ Ki | Pontos IP vagy CIDR (`203.0.113.0/24`) |

</details>

<details>
<summary><strong>📊 Napló fül</strong></summary>
<br>

Sikertelen bejelentkezési próbálkozások listája IP-cím, ország és város szerint, 1 nap / 1 hét / 1 hónap / 1 év bontású összesítővel. Legfeljebb 1 évig tárolva, kézzel bármikor törölhető.

</details>

<br>

## 🛡️ Biztonsági modell

```
┌─────────────────────────────────────────────────────────┐
│  Mesterjelszó  →  wp_hash_password()  →  wp_options      │
│  (egyirányú, visszafejthetetlen - a hitelesítéshez)      │
├─────────────────────────────────────────────────────────┤
│  Mesterjelszó  →  AES-256-CBC (AUTH_KEY alapú kulcs)     │
│  (visszafejthető - KIZÁRÓLAG admin megtekintéshez)       │
├─────────────────────────────────────────────────────────┤
│  Munkamenet    →  SHA-256 token  →  HttpOnly/SameSite    │
│  próbálkozás   →  anonimizált IP-hash  →  transient       │
│  napló         →  valós IP + geo  →  egyedi DB tábla     │
└─────────────────────────────────────────────────────────┘
```

- A **látogatói jelszó-ellenőrzés** mindig, kizárólag az egyirányú hash-t használja.
- A **"jelszó megtekintése"** funkcióhoz egy második, visszafejthető, AES-256-CBC-vel titkosított másolat is tárolásra kerül — a kulcs a `wp-config.php`-ban (nem az adatbázisban!) tárolt `AUTH_KEY`/`AUTH_SALT` értékekből származik.
- A **próbálkozás-korlátozás** anonimizált IP-hash-t használ, sosem olvasható IP-t.
- A **bejelentkezési napló** — biztonsági auditálási céllal, szándékosan — valós IP-címet tárol, legfeljebb 1 évig.

> [!WARNING]
> **503-as HTTP állapotkód (opcionális, alapból KIKAPCSOLVA):** egyes tárhelyek/CDN-ek/biztonsági bővítmények (pl. Wordfence, LiteSpeed Cache) a saját hibaoldalukkal helyettesíthetik a 503-as választ, ami a teljes weboldalt elérhetetlenné teheti. Ezért 1.0.2 óta ez a beállítás alapból ki van kapcsolva — csak akkor kapcsold be, ha biztos vagy benne, hogy a tárhelyed helyesen kezeli.

<br>

## ❓ GYIK

<details>
<summary>Kizárhatom saját magam a weboldalamról?</summary>
<br>

Nem — bejelentkezett, adminisztrátori jogosultsággal rendelkező felhasználók számára a `wp-admin` irányítópult mindig elérhető marad.
</details>

<details>
<summary>Miért nem működik a Jetpack bekapcsolt védelem mellett?</summary>
<br>

Ellenőrizd, hogy 1.0.1 vagy újabb verziót használsz-e — ez tartalmazza az automatikus XML-RPC mentesítést és a Jetpack REST route-kivételeket.
</details>

<details>
<summary>A védelem bekapcsolása után "Error 503"-at kapok, minden elérhetetlen!</summary>
<br>

Ez az 1.0.2-ben javított hiba. Frissíts a legújabb verzióra — a jelszókérő oldal mostantól alapból HTTP 200-at küld.
</details>

<details>
<summary>Hogyan adhatok hozzá kivételt egy másik bővítményhez?</summary>
<br>

Biztonság fül → "AJAX végpont kivételek" (admin-ajax.php action-nevekhez) vagy "REST API kivételek" (REST route-prefixekhez).
</details>

<br>

## 📁 Könyvtárszerkezet

```
mesterjelszo/
├── mesterjelszo.php              # Fő plugin fájl
├── uninstall.php                 # Eltávolításkori takarítás
├── readme.txt                    # WordPress.org szabványos leírás
├── README.md                     # Ez a fájl (GitHub)
├── changelog.txt                 # Részletes changelog
├── includes/
│   ├── class-mesterjelszo.php
│   ├── class-mesterjelszo-activator.php
│   ├── class-mesterjelszo-deactivator.php
│   ├── class-mesterjelszo-security.php
│   ├── class-mesterjelszo-admin.php
│   ├── class-mesterjelszo-public.php
│   └── class-mesterjelszo-login-log.php
├── admin/
│   ├── css/mesterjelszo-admin.css
│   ├── js/mesterjelszo-admin.js
│   └── partials/settings-page.php
├── public/
│   ├── css/mesterjelszo-public.css
│   ├── js/mesterjelszo-public.js
│   └── partials/gate-page.php
└── languages/
    └── mesterjelszo.pot
```

<br>

## 📜 Changelog

Rövid összefoglaló — a teljes, részletes napló a **[changelog.txt](changelog.txt)** fájlban.

| Verzió | Típus | Összefoglaló |
|---|---|---|
| **1.0.2** | 🔥 Hotfix | 503 → 200 alapértelmezett válasz, bejelentkezési napló, megbízható IP-k, AJAX kivételek |
| **1.0.1** | ✨ Funkció | Jelszó-megtekintés, "Emlékezz rám", Jetpack/REST/XML-RPC kompatibilitás |
| **1.0.0** | 🎉 Kiadás | Első nyilvános verzió |

<br>

## 🤝 Közreműködés

Hibát találtál vagy javaslatod van? Nyiss egy [Issue-t](https://github.com/galandras12/Wordpress_mesterjelszo/issues), vagy küldj egy Pull Requestet.

<br>

## 📄 Licenc

GPLv2 vagy újabb — lásd a [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) fájlt.

<br>

---

<div align="center">

Készítette: **galandras12 + AI**

[GitHub profil](https://github.com/galandras12) · [Repository](https://github.com/galandras12/Wordpress_mesterjelszo)

</div>
