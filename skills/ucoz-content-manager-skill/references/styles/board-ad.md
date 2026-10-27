# Style: board ad (объявление)

**Use for:** the board module: sell, buy, give away, services, rent, jobs.

**Reader's goal:** know in 5 seconds what, how much, where, in what condition, and how to contact.

## Fields

| Field | Content |
|---|---|
| `title` | Item/service + key parameter + condition/place: «Детская коляска Cybex, 2023, Казань» |
| `ad_type` | Value id of filter 3 (Продам / Куплю / Отдам даром / Услуги…) |
| `description` | The short ad text (shown in lists): what, state, price, place — 150–400 characters |
| `message` (`message_type: html`) | Full description: details, what's included, reason, conditions of meeting/delivery |
| `name` | Contact person (the user's choice; the page also shows the posting account login) |
| `author_email`, `author_phone` | Only what the user gave |
| `till_date` | Defaults to +31 days; set if the user named a deadline |
| images | 1–6 real photos of the item via `image_upload_ids` + `$IMAGEn$` in `message` (gallery thumbnails are fine for ads) |

## HTML skeleton (`message`)

```html
<p>Что продаётся/предлагается, состояние, причина.</p>
<ul>
  <li><strong>Цена:</strong> … (торг / без торга)</li>
  <li><strong>Состояние:</strong> …</li>
  <li><strong>Комплект:</strong> …</li>
  <li><strong>Где:</strong> район/город, самовывоз/доставка</li>
</ul>
<p>$IMAGE1$ $IMAGE2$ $IMAGE3$</p>
<p>Как связаться: …</p>
```

## Rules

- Facts only from the owner: price, condition, defects, location. Never invent defects or their absence.
- Real photos only — generated images are not acceptable for item ads (they mislead buyers). For services an illustration is fine if labelled.
- One ad = one item/service.
- No CAPS, no «!!!», no «СРОЧНО» unless the owner insists.
- Personal data: publish only the contacts the user explicitly gave for this ad.

## Checklist

- [ ] Title has item + key parameter + place.
- [ ] Price and condition are stated (or «цена по запросу» if the owner said so).
- [ ] `ad_type` matches the intent.
- [ ] Photos are real and referenced in `message`.
- [ ] Contacts are the owner's chosen ones.

## Brief template

```text
Тип: продам / куплю / отдам / услуги
Что (марка, модель, год, размер):
Состояние, дефекты:
Цена, торг:
Где, самовывоз/доставка:
Контактное лицо, почта, телефон:
Фото (сколько, реальные):
Срок размещения:
```
