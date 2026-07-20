# Mesterjelszó - Teljes Weboldal Jelszóvédelem WordPress-hez

> Védd meg a teljes weboldalt egyetlen mesterjelszóval - v1.0.1

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![WordPress: 6.4+](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)
![PHP: 8.0+](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)
![Version: 1.0.1](https://img.shields.io/badge/Version-1.0.1-green.svg)

## 🔐 Funkciók

### Fő Védelem
- **Teljes weboldal zárolása** egyetlen jelszóval
- **Oldalak, bejegyzések, egyedi tartalomtípusok** védelme
- **REST API védelem** - teljesen blokkolva vagy szelektív engedélyezés
- **Bejelentkezési felület védelme**
- **Admin panel védelem** - csak jogosult adminok férhető hozzá

### Biztonság (1.0.1)
- ✅ **Jelszó megjelenítés** admin számára (toggle + copy gomb)
- ✅ **Brute-force védelem** - próbálkozás-limit és ideiglenes zárolás
- ✅ **Egyirányú hash tárolás** (WordPress core sztanderd)
- ✅ **HttpOnly és SameSite sütik** - ellenéll a CSRF támadásoknak
- ✅ **Nonce védelem** minden formon
- ✅ **GDPR-barát** - IP címek csak anonimizált hash formában

### Felhasználói Felület
- 🎬 **Modern, reszponzív design** - mobilon és asztalin egyaránt
- 🎪 **Kártya-alapú admin felület** élő előnézettel
- 🖼 **Testreszabható háttér** - szín vagy kép
- 🎨 **Váltható színmód** - világos / sötét / automatikus
- 🎤 **Felölthető logó** és weboldalnév
- ✍️ **Teljesen testreszabható üzenet**
- ♿ **Akadálymentes (WCAG kompatibilis)**

### 1.0.1 Üj Funkciók

#### 1. "Jegyezz meg" Gomb
```
✅ Opcionális gomb a jelszókérő felületen
✅ Admin panelen be/ki kapcsolható
✅ Alapbén kikapcsolt (biztonság)
✅ 15 napos munkamenet (konfigurálható)
✅ 24 órás alapmunkamenet, ha nincs bejelölve
```

#### 2. Jelszó Megjelenítés
```
✅ Admin számára látható
✅ Toggle gomb - elrejtés/megjelenítés
✅ Copy to clipboard funkció
✅ Csak manage_options jogosultság
```

#### 3. Szelektív REST API Engedélyezés
```
✅ Admin panelen be/ki kapcsolható
✅ Jetpack és más szolgáltatások számára
✅ Engedélyezett: /wp-json/jetpack/*
✅ Többi végpont: blokkolva
```

---

## 🚀 Gyors Kezdés

### 1. Telepítés

**Autó mód:**
```
WordPress Admin > Bővítmények > Új hozzáadása > "Mesterjelszó" keresése > Telepítés
```

**Kézi mód:**
```bash
# Klonozd a repót:
git clone https://github.com/galandras12/Wordpress_mesterjelszo.git wp-content/plugins/mesterjelszo

# Vagy töltsd fel a ZIP-et
WordPress Admin > Bővítmények > Bővítmény feltöltése
```

### 2. Aktiválás

```
WordPress Admin > Bővítmények > Mesterjelszó > Aktiválás
```

### 3. Alapvető Beállítás

1. Menj: **Mesterjelszó** (bal oldali menü)
2. Írd be a **mesterjelszót** (min. 6 karakter)
3. **Kapcsold be a védelmet**
4. Mentsd el: **Beállítások mentése**

### 4. Opcionális: Jelszó Megjelenítés (Admin)

1. Menj: **Mesterjelszó** > **Biztonság fül**
2. Jelöld be: "Mesterjelszó megjelenítése az admin panelen"
3. Mostmár az admin látja a jelszót (toggle gomb, copy funkció)

### 5. Opcionális: "Jegyezz meg" Gomb

1. Menj: **Mesterjelszó** > **Felhasználói felület fül**
2. Jelöld be: "'Jegyezz meg' gomb megjelenítése"
3. Állítsd be a napok számát (alapbén 15)
4. A felhasználók most opcionálisan hosszabb munkamenetet kaphatnak

### 6. Opcionális: Jetpack REST API Engedélyezés

1. Menj: **Mesterjelszó** > **Intégrációk fül**
2. Jelöld be: "REST API szelektív engedélyezés"
3. Jetpack mostmár képes kapcsolatot építeni

---

## ⚙️ Beállítások

### Védelem
- **Védelem engedélyezése** - be/ki kapcsol
- **Mesterjelszó** - min. 6 karakter, ajánlott 12+
- **Admin-ok megobyalése** - bejelentkezett adminok átugorhatják a védelmet

### Megjelenítés
- **Logo** - felölthető logó
- **Weboldalnév megjelenítése** - `bloginfo('name')`
- **Üzenet** - testreszabható HTML (korlátok: `<strong>, <em>, <a>, <p>, <br>`)
- **Háttér typ** - szín vagy kép
- **Háttérszín** - hex kód
- **Háttér kép** - feltölthető háttér
- **Átlátszóság** - 0-100% (háttérkép)
- **Színmód** - világos / sötét / automatikus
- **Akcentszín** - felhasználói felület (hex)
- **Szöveg szín** - hex kód

### Munkamenet
- **Munkamenet hossza** - óra (alapbén 24)
- **Max. próbálkozások** - limit (alapbén 5)
- **Zárolási időtartam** - percek (alapbén 15)
- **"Jegyezz meg" napok** - nap (alapbén 15) - csak ha engedélyezve

### Biztonság
- **Jelszó megjelenítés** - admin panelen

### Intégrációk
- **REST API szelektív engedélyezés** - Jetpack stb.

---

## 🌍 Fordítások

- 🇭🇺 **Magyar** - `mesterjelszo-hu_HU.po`
- 🇬🇧 **English** - `mesterjelszo-en_US.po`

A fordítások teljesek és karbantartottak.

---

## ⚠️ Ismert Korlátozások

### Feltöltött Médiafichiers
A WordPress a `/wp-content/uploads` mappában lévő fájlokat közvetlenül (PHP nélkül) kiszolgálja. Ez azt jelenti, hogy ezek a fájlok a jelszóvédelem alatt is elérhetőek.

**Megoldás:** Apache/Nginx konfigurálása `.htaccess` vagy szerver beállítások útján.

### URL Közvetlen Elérése
Bizonyos félalgoritmusok közvetlenül férhetnek hozzá az URL-hez JSON formátumban (pl. `wp-json/wp/v2/posts`).

**Megoldás:** A szelektív REST API engedélyezés csak szükséges végpontokhoz engedélyez hozzáférést.

---

## 🐛 Hibajelentés

Hibajelentéseket az alábbi helyen adhatsz be:

```
https://github.com/galandras12/Wordpress_mesterjelszo/issues
```

---

## 📄 Licenc

GNU General Public License v2.0 vagy későbbi verzió

- 📖 **Angol verzió:** [LICENSE](LICENSE)
- 📖 **Magyar fordítás:** [LICENSE-hu.md](LICENSE-hu.md)

---

## 👤 Szerző

**Mesterjelszó**
- GitHub: https://github.com/galandras12/
- Repo: https://github.com/galandras12/Wordpress_mesterjelszo

---

## 🎯 Verzióhistória

### v1.0.1 (Aktuális)
- ✨ Jelszó megjelenítés admin panelen
- ✨ "Jegyezz meg" gomb 15 napos munkamenethez
- ✨ Szelektív REST API engedélyezés (Jetpack)
- 🔧 Beállítások bővítése

### v1.0.0 (Alap)
- 🎉 Első kiadás
- Teljes weboldal jelszóvédelem
- Testreszabható admin felület
- Brute-force védelem

---

## 💡 Tanácsok a Biztonsághoz

1. **Erős jelszó:** Min. 12 karakter, vegyes nagy/kis, szám, speciális karakter
2. **SSL/HTTPS:** Szorgalmazz HTTPS-t minden szerver szinten
3. **Friss WordPress:** Tarts lépést a WordPress frissítésekkel
4. **Rendszeres mentés:** Készítsd el az adatbázis biztonsági másolatait
5. **Admin jogok:** Csak megbízható felhasználóknak adj admin jogot

---

## 📞 Támogatás

- 🐛 **Hibák:** https://github.com/galandras12/Wordpress_mesterjelszo/issues
- 💬 **Vita:** https://github.com/galandras12/Wordpress_mesterjelszo/discussions

---

**Készült ❤️ Magyarországon**
