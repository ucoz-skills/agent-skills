# Style: file description (каталог файлов)

**Use for:** an entry in the load module (and similar stuff entries): a program, template, document, book, archive.

**Reader's goal:** know what the file is, whether it fits their system/task, and that it's safe to download.

## Fields

| Field | Content |
|---|---|
| `title` | Name + version/type: «Шаблон сметы на ремонт (Excel, 2026)» |
| `description` (required) | What it is and what for, 1–2 sentences, 150–300 characters |
| `message` (required) | What's inside, requirements, how to use, licence — only known facts |
| `archive_upload_id` or `archlink` + `archsize` | The file itself — an entry about a file must have one (claims like «мы собрали коллекцию» on an entry without a file are a mismatch to report) |
| optional fields (`versions`, `licence`, `os`, `language`, `screenshot`) | Fill only if turned on in `fields` and known |

## HTML skeleton (`message`)

```html
<p>Что это и для чего — одним-двумя предложениями.</p>
<ul>
  <li><strong>Формат:</strong> …</li>
  <li><strong>Размер:</strong> …</li>
  <li><strong>Требования:</strong> …</li>
  <li><strong>Лицензия:</strong> …</li>
</ul>
<h2>Что внутри</h2>
<ul><li>…</li></ul>
<h2>Как пользоваться</h2>
<ol><li>…</li></ol>
<figure><a href="$IMG_URL1$" class="ulightbox"><img src="$IMG_URL1$" alt="Скриншот: …" class="ulightbox" loading="lazy"></a></figure>
```

Omit any line or block you have no fact for. Short descriptions (<1 500 characters) need no `<h2>`.

## Meta

- meta_title: «Name — скачать (format)» or «Name: what it does» (≤60).
- meta_description: what it is + format + one key requirement (140–160).

## Avoid

- «Уникальная коллекция», «всё необходимое», «откройте для себя».
- Versions, sizes, OS requirements, licences the user didn't give.
- Promising «без вирусов» — say what was checked only if it was.

## Checklist

- [ ] A file or external link is attached.
- [ ] Format, size and requirements are exact or absent.
- [ ] The licence/rights to distribute are known (ask if not).
- [ ] Screenshots are real (generated images can't show a real program).

## Brief template

```text
Файл (название, версия, формат, размер):
Что внутри и для чего:
Требования (ОС, программа):
Лицензия / право распространять:
Как пользоваться:
Скриншоты:
```
