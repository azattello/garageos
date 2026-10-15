# GarageOS

## Операционная система для автосервисов

**Версия:** 1.0
**Тип:** SaaS Web Application
**Назначение:** управление операционной деятельностью автосервисов
**Целевая аудитория:** СТО, детейлинг, шиномонтаж, ходовка, моторные сервисы, автоэлектрика, замена масла, диагностика, кузовные сервисы, автомойки и другие автомобильные сервисные предприятия.

---

# 1. Концепция продукта

GarageOS — облачная SaaS-платформа для полного управления автосервисным бизнесом.

Главная идея:

> **Все процессы автосервиса находятся в одной системе.**

От первого обращения клиента до завершения ремонта, оплаты и последующего возврата клиента.

Основной рабочий цикл:

```text
Клиент
   ↓
Автомобиль
   ↓
Запись
   ↓
Заказ-наряд
   ↓
Диагностика
   ↓
Работы
   ↓
Запчасти
   ↓
Мастер
   ↓
Статусы
   ↓
Фото
   ↓
Оплата
   ↓
Выдача автомобиля
   ↓
История
   ↓
Напоминание о следующем обслуживании
   ↓
Повторный визит
```

GarageOS не является только CRM.

Платформа должна объединять:

* CRM клиентов;
* автомобили;
* запись и календарь;
* заказ-наряды;
* Kanban;
* сотрудников;
* услуги;
* запчасти;
* склад;
* оплаты;
* расходы;
* управленческую аналитику;
* уведомления;
* историю автомобиля;
* клиентский доступ;
* SaaS-подписку.

---

# 2. Основная ценность

После входа владелец должен за несколько секунд понимать:

* сколько машин сегодня запланировано;
* сколько машин сейчас в работе;
* какие заказы готовы;
* какие заказы просрочены;
* какие автомобили ждут запчасти;
* сколько денег получено;
* сколько клиентов должны;
* сколько новых клиентов;
* какие мастера загружены;
* какие услуги приносят больше заказов;
* какие запчасти заканчиваются.

---

# 3. Для каких бизнесов подходит GarageOS

Система не должна быть ограничена только классическим СТО.

Поддерживаемые направления:

* СТО общего профиля;
* шиномонтаж;
* ходовая;
* подвеска;
* ремонт двигателя;
* ремонт КПП;
* замена масла;
* ТО;
* автоэлектрика;
* компьютерная диагностика;
* кондиционеры;
* кузовной ремонт;
* покраска;
* детейлинг;
* полировка;
* керамика;
* химчистка;
* оклейка;
* автомойка;
* автостекла;
* аккумуляторы;
* комбинированные автосервисы.

## Принцип

Мы не создаем 15 разных приложений.

Мы создаем одно ядро:

```text
Клиент
Автомобиль
Услуга
Запись
Заказ
Сотрудник
Оплата
История
```

А конкретный сервис сам настраивает:

* услуги;
* категории;
* сотрудников;
* цены;
* специализации;
* статусы;
* фотографии;
* рабочие процессы.

---

# 4. Целевая бизнес-модель

GarageOS является multi-tenant SaaS.

Каждый клиент платформы получает собственную организацию:

```text
GarageOS
│
├── Organization A — СТО "АвтоМастер"
│   ├── сотрудники
│   ├── клиенты
│   ├── автомобили
│   └── заказы
│
├── Organization B — "Detail Lab"
│   ├── сотрудники
│   ├── клиенты
│   ├── автомобили
│   └── заказы
│
└── Organization C — "Master Oil"
```

Данные организаций должны быть строго изолированы.

---

# 5. Тарифная модель

## 5.1. Trial

**14 дней — бесплатно**

Полный доступ к выбранному тарифу.

После завершения trial:

* доступ ограничивается;
* данные сохраняются;
* отображается экран подписки;
* пользователь может продолжить после оплаты/активации.

---

## 5.2. Start

**39 900 ₸ / месяц**

Целевая аудитория:

* маленькие СТО;
* шиномонтаж;
* небольшие детейлинги;
* 1–3 мастера.

Ограничение:

**до 3 пользователей.**

Функции:

* клиенты;
* автомобили;
* записи;
* календарь;
* заказ-наряды;
* Kanban;
* услуги;
* сотрудники;
* история автомобиля;
* фотографии;
* оплаты;
* задолженность;
* базовая аналитика;
* уведомления;
* клиентская страница заказа.

---

## 5.3. Business

**69 900 ₸ / месяц**

Целевая аудитория:

* полноценные СТО;
* сервисы с несколькими мастерами;
* детейлинг-центры;
* комбинированные сервисы.

Ограничение:

**до 10 пользователей.**

Включает всё из Start:

* склад;
* запчасти;
* остатки;
* движения товара;
* расходы;
* расширенную аналитику;
* расширенные финансовые отчеты;
* загрузку сотрудников;
* контроль задолженности;
* расширенные уведомления.

---

## 5.4. Pro — будущий тариф

На MVP не реализовывать полностью.

В дальнейшем:

* несколько филиалов;
* WhatsApp API;
* SMS;
* онлайн-запись;
* платежные интеграции;
* автоматические напоминания;
* API;
* расширенные права;
* несколько складов;
* расширенная аналитика;
* автоматические отчеты;
* интеграции бухгалтерии.

---

# 6. Выбранный стек

## Backend

* **PHP 8.3+**
* **Laravel 12**
* Laravel Eloquent
* Laravel Validation
* Laravel Policies / Gates
* Laravel Events
* Laravel Notifications
* Laravel Scheduler
* Laravel Cache
* Laravel Storage

## Frontend

* **React**
* **Inertia.js**
* **Tailwind CSS**
* **Bklit UI**

Bklit UI является основной UI/component foundation.

Не использовать другую библиотеку компонентов вместо Bklit UI без необходимости.

## Database

Основной вариант:

* MySQL 8.

Код и миграции должны проектироваться так, чтобы переход между MySQL и MariaDB был максимально безболезненным.

## Charts

Все аналитические графики GarageOS реализуются через официальные Bklit UI chart components из shadcn registry `@bklit`.

Chart.js и hand-drawn SVG/HTML charts не добавлять. Для новых графиков использовать workspace skill `.agents/skills/bklit-ui`, подобрать официальный registry component и сохранить GarageOS chart theme tokens.

## Files

MVP:

* Laravel Storage.

Будущее:

* S3-compatible storage.

---

# 7. Production архитектура

Production не должен требовать постоянно работающего Node.js backend.

Архитектура:

```text
Browser
   ↓
HTTPS
   ↓
Nginx / Apache
   ↓
PHP-FPM
   ↓
Laravel
   ↓
MariaDB / MySQL
```

React собирается:

```text
npm install
npm run build
```

После сборки production использует готовые frontend assets.

Node.js нужен преимущественно для frontend build pipeline.

---

# 8. Требования к хостингу

Рекомендуется:

* PHP 8.3+;
* Laravel 12;
* MariaDB/MySQL;
* Composer;
* HTTPS;
* Cron;
* файловое хранилище;
* возможность настроить `/public` как document root;
* желательно SSH;
* резервные копии;
* возможность расширения ресурсов;
* возможность настройки environment variables.

SSH не является частью runtime-архитектуры, но крайне желателен для удобной эксплуатации.

---

# 9. Общая архитектура приложения

```text
GarageOS Platform
│
├── SaaS Layer
│   ├── Organizations
│   ├── Subscriptions
│   ├── Plans
│   └── Platform Admin
│
└── Business Layer
    ├── Clients
    ├── Cars
    ├── Appointments
    ├── Orders
    ├── Services
    ├── Employees
    ├── Parts
    ├── Inventory
    ├── Payments
    ├── Expenses
    ├── Notifications
    └── Analytics
```

---

# 10. Multi-tenant модель

Каждая организация имеет `organization_id`.

Пример:

```text
organizations
     │
     ├── users
     ├── clients
     ├── cars
     ├── appointments
     ├── orders
     ├── services
     ├── parts
     ├── payments
     └── expenses
```

Backend всегда определяет текущую организацию из авторизованного пользователя/контекста.

Нельзя доверять:

```text
organization_id
```

полученному напрямую от frontend.

---

# 11. Роли

## 11.1. Platform Admin

Системный администратор GarageOS.

Имеет доступ:

* ко всем организациям;
* подпискам;
* тарифам;
* trial;
* пользователям;
* SaaS статистике;
* активности;
* блокировке организации.

Platform Admin не является сотрудником конкретной организации.

---

## 11.2. Owner

Владелец автосервиса.

Доступ:

* dashboard;
* клиенты;
* автомобили;
* записи;
* заказы;
* Kanban;
* сотрудники;
* услуги;
* склад;
* запчасти;
* финансы;
* расходы;
* аналитика;
* уведомления;
* настройки;
* подписка.

---

## 11.3. Manager

Менеджер / администратор.

Доступ:

* клиенты;
* автомобили;
* записи;
* календарь;
* заказы;
* работы;
* фотографии;
* назначение мастеров;
* статусы;
* оплаты.

По умолчанию не имеет доступа к:

* подписке;
* системным настройкам;
* полной финансовой аналитике;
* критическим административным функциям.

---

## 11.4. Mechanic

Механик / мастер.

Доступ:

* назначенные заказы;
* клиент по заказу;
* автомобиль;
* работы;
* комментарии;
* фотографии;
* рабочие статусы.

Не должен видеть автоматически:

* полную прибыль компании;
* расходы;
* подписку;
* других клиентов;
* административные настройки.

---

## 11.5. Client

Клиент автосервиса.

Доступ к:

* своим автомобилям;
* своим заказам;
* статусам;
* выполненным работам;
* доступным фотографиям;
* оплатам;
* истории обслуживания;
* своим записям.

Не видит:

* внутренние комментарии;
* закупочные цены;
* себестоимость;
* маржу;
* расходы;
* зарплаты;
* других клиентов;
* внутреннюю аналитику.

---

# 12. Авторизация

## Регистрация

Поля:

* название бизнеса;
* имя владельца;
* телефон;
* email;
* пароль;
* подтверждение пароля.

После регистрации автоматически создаются:

```text
Organization
User
Subscription
Trial
```

Пользователь получает роль:

```text
Owner
```

## Login

* email;
* пароль;
* remember me.

## Дополнительно

* logout;
* reset password;
* password confirmation;
* validation;
* rate limiting.

SMS не входит в MVP.

---

# 13. Структура приложения

Основная навигация:

```text
Обзор

Работа
 ├── Клиенты
 ├── Автомобили
 ├── Записи
 ├── Заказы
 └── Kanban

Управление
 ├── Сотрудники
 ├── Услуги
 └── Запчасти

Финансы
 ├── Финансы
 ├── Расходы
 └── Аналитика

Система
 ├── Уведомления
 ├── Настройки
 └── Подписка
```

---

# 14. Dashboard

Dashboard — центральная точка управления.

## Верхняя часть

* название страницы;
* дата;
* глобальный поиск;
* notifications;
* профиль пользователя.

## KPI

Показать:

* автомобилей сегодня;
* активных заказов;
* готовых автомобилей;
* выручку сегодня;
* новых клиентов;
* просроченных заказов;
* общий долг клиентов.

## Быстрые действия

```text
+ Новый клиент
+ Новый автомобиль
+ Новая запись
+ Новый заказ
+ Оплата
```

## Сегодня

Показывать:

* ближайшие записи;
* автомобили в работе;
* готовые автомобили;
* просроченные заказы.

## Графики

Периоды:

* сегодня;
* 7 дней;
* 30 дней;
* произвольный.

Данные:

* выручка;
* расходы;
* заказы;
* средний чек.

## Activity

Последние события:

* создан клиент;
* создан заказ;
* изменен статус;
* добавлена оплата;
* добавлена фотография.

---

# 15. Клиенты

## Список клиентов

Колонки:

* имя;
* телефон;
* автомобили;
* последний визит;
* заказы;
* сумма;
* задолженность;
* дата создания.

Функции:

* поиск;
* фильтры;
* сортировка;
* пагинация;
* создание;
* просмотр;
* редактирование;
* архивирование.

Поиск:

* имя;
* телефон;
* номер;
* VIN;
* госномер.

---

# 16. Карточка клиента

Header:

* имя;
* avatar/initials;
* телефон;
* email;
* действия.

Информация:

* количество автомобилей;
* количество заказов;
* общая сумма;
* задолженность;
* последний визит.

Tabs:

```text
Обзор
Автомобили
Заказы
Финансы
```

Quick actions:

```text
Добавить автомобиль
Создать запись
Создать заказ
```

---

# 17. Автомобили

Поля:

* клиент;
* марка;
* модель;
* год;
* госномер;
* VIN;
* цвет;
* пробег;
* тип топлива;
* комментарий;
* фото.

---

# 18. Карточка автомобиля

Показывать:

* марку;
* модель;
* госномер;
* владельца;
* пробег;
* активный заказ;
* последнее обслуживание;
* общую сумму обслуживания.

CTA:

> * Новый заказ

Tabs:

```text
Обзор
История
Фото
Оплаты
```

---

# 19. История автомобиля

Хронология:

```text
19.09.2026
Замена масла
35 000 ₸

01.08.2026
Замена колодок
90 000 ₸

12.05.2026
Диагностика
15 000 ₸
```

Каждое событие открывает соответствующий заказ.

---

# 20. Записи

## Создание записи

Поля:

* клиент;
* автомобиль;
* дата;
* время;
* длительность;
* услуга;
* мастер;
* комментарий.

## Статусы

```text
Ожидает
Подтверждена
Клиент приехал
Завершена
Отменена
```

---

# 21. Календарь

Режим:

* день;
* неделя.

Событие отображает:

* время;
* клиента;
* автомобиль;
* услугу;
* мастера;
* статус.

Действия:

* открыть;
* изменить;
* отменить;
* создать заказ.

---

# 22. Конфликты расписания

Перед созданием записи проверить:

* занят ли мастер;
* пересекается ли время;
* занят ли рабочий пост — если модуль постов будет включен.

При конфликте:

> Мастер уже занят в это время.

Пользователь должен иметь возможность выбрать другое время.

---

# 23. Заказ-наряд

Заказ является **центральной бизнес-сущностью GarageOS**.

## Источник создания

Заказ можно создать:

* из записи;
* из карточки клиента;
* из карточки автомобиля;
* напрямую.

## Поля

* номер;
* клиент;
* автомобиль;
* мастер;
* дата открытия;
* плановая дата окончания;
* статус;
* услуги;
* работы;
* запчасти;
* скидка;
* комментарии;
* фотографии.

---

# 24. Статусы заказа

Базовые:

```text
Новый
↓
Принят
↓
Диагностика
↓
В работе
↓
Ожидание запчастей
↓
Готов
↓
Выдан
↓
Закрыт
```

Дополнительно:

```text
Отменен
```

Статусы должны быть храниться в базе, а не быть полностью захардкожены в frontend.

В будущем владелец может добавлять собственные статусы.

---

# 25. Работы

У каждой работы:

* услуга;
* название;
* описание;
* количество;
* цена;
* скидка;
* сумма;
* исполнитель;
* статус.

Пример:

```text
Замена масла
1 × 8 000 ₸
= 8 000 ₸
```

---

# 26. Запчасти в заказе

Каждая позиция:

* запчасть;
* название;
* количество;
* цена продажи;
* себестоимость;
* сумма.

Если склад включен:

```text
Заказ
 ↓
Добавление запчасти
 ↓
Резерв/списание
 ↓
Inventory transaction
```

---

# 27. Расчет заказа

Формула:

```text
Subtotal =
сумма работ +
сумма запчастей

Total =
Subtotal - Discount

Balance =
Total - Successful Payments
```

Расчеты выполняются на backend.

Frontend не является источником истины.

---

# 28. Пример заказа

```text
Работы                  120 000 ₸
Запчасти                 80 000 ₸
---------------------------------
Subtotal                200 000 ₸

Скидка                   10 000 ₸
---------------------------------
Итого                   190 000 ₸

Оплачено                100 000 ₸
---------------------------------
К оплате                 90 000 ₸
```

---

# 29. Kanban

Колонки:

```text
НОВЫЕ
ДИАГНОСТИКА
В РАБОТЕ
ЗАПЧАСТИ
ГОТОВЫ
ВЫДАНЫ
```

Карточка:

```text
#1042

BMW X5
A123BC

Замена масла
85 000 ₸

Мастер: Азамат
Срок: сегодня
```

Поддержать drag & drop.

При переносе:

1. backend проверяет право;
2. изменяет status;
3. пишет activity log;
4. создает event;
5. обновляет UI.

---

# 30. Фото

Фото могут быть привязаны:

* к заказу;
* к автомобилю.

Категории:

* до;
* повреждение;
* процесс;
* после;
* другое.

Функции:

* загрузка;
* drag & drop;
* preview;
* lightbox;
* удаление;
* caption;
* дата;
* автор;
* visible_to_client.

---

# 31. Клиентские фотографии

Для каждой фотографии:

```text
visible_to_client = true / false
```

Если false:

клиент ее не видит даже при доступе к заказу.

---

# 32. Публичная страница заказа

Для каждого заказа создается защищенная ссылка.

Пример:

```text
GarageOS.kz/order/AF-1042/<secure-token>
```

Клиент видит:

* логотип;
* название сервиса;
* номер заказа;
* автомобиль;
* статус;
* работы;
* запчасти;
* доступные фото;
* итог;
* оплачено;
* долг.

Не показывать:

* себестоимость;
* закупочные цены;
* внутренние комментарии;
* служебные данные.

---

# 33. Клиентский личный кабинет

Необязательная первая версия, но архитектура должна поддерживать его.

После регистрации клиента:

```text
Client
 ↓
My GarageOS
```

Возможности:

* мои автомобили;
* текущие заказы;
* история;
* фото;
* оплаты;
* записи;
* рекомендации;
* будущие напоминания.

---

# 34. Услуги

Услуга содержит:

* название;
* категория;
* описание;
* базовую цену;
* длительность;
* active/inactive.

Примеры:

* замена масла;
* диагностика;
* тормозная система;
* ремонт ходовой;
* полировка;
* химчистка;
* керамика;
* шиномонтаж;
* автоэлектрика;
* ремонт двигателя.

---

# 35. Категории услуг

По умолчанию:

* ТО;
* диагностика;
* двигатель;
* ходовая;
* тормоза;
* электрика;
* кондиционер;
* шиномонтаж;
* кузов;
* детейлинг;
* мойка;
* стекла;
* другое.

Пользователь может создавать собственные категории.

---

# 36. Сотрудники

Поля:

* имя;
* телефон;
* email;
* роль;
* специализация;
* активность;
* дата добавления.

Специализации:

* механик;
* моторист;
* электрик;
* диагност;
* детейлер;
* мастер-приемщик;
* шиномонтажник.

---

# 37. Статистика сотрудника

Показывать:

* заказов;
* выполненных работ;
* суммы;
* текущую загрузку;
* завершенные заказы.

Сложную автоматическую систему расчета зарплаты не делать в MVP.

---

# 38. Запчасти

Карточка:

* название;
* SKU;
* категория;
* поставщик;
* закупочная цена;
* цена продажи;
* остаток;
* минимальный остаток;
* единица;
* active.

---

# 39. Склад

Операции:

* приход;
* расход;
* корректировка;
* возврат.

Каждая операция должна иметь:

* дату;
* пользователя;
* количество;
* тип;
* основание;
* связанный заказ, если есть.

---

# 40. Остатки

Пример:

```text
Масло 5W-30
Остаток: 3
Минимум: 5

⚠️ Низкий остаток
```

Когда остаток ниже `min_stock`, создается notification.

---

# 41. Платежи

Поддерживаем:

* наличные;
* карта;
* Kaspi;
* перевод;
* другое.

Один заказ может иметь несколько платежей.

Пример:

```text
100 000 ₸ — Kaspi
50 000 ₸ — карта
50 000 ₸ — наличные
```

---

# 42. Правила платежей

Статусы:

* pending;
* successful;
* cancelled.

Отмененный платеж не участвует в расчете долга.

Удалять платеж физически не рекомендуется.

Предпочтительнее:

```text
status = cancelled
```

и запись в Activity Log.

---

# 43. Задолженность

Система автоматически рассчитывает:

```text
Total
-
Successful Payments
=
Balance
```

Статусы:

```text
Оплачено
Частично
Не оплачено
```

---

# 44. Расходы

Категории:

* аренда;
* зарплата;
* запчасти;
* расходники;
* реклама;
* коммунальные;
* оборудование;
* транспорт;
* другое.

Поля:

* категория;
* сумма;
* дата;
* описание;
* пользователь.

---

# 45. Финансы

Основные показатели:

* доход;
* расходы;
* разница;
* средний чек;
* долг клиентов.

Фильтр:

* сегодня;
* неделя;
* месяц;
* год;
* произвольный период.

---

# 46. Аналитика

## Revenue

* по дням;
* по неделям;
* по месяцам;
* по произвольному периоду.

## Orders

* количество;
* завершенные;
* отмененные;
* средний чек;
* средняя длительность.

## Clients

* новые;
* повторные;
* активные;
* с задолженностью.

## Services

* количество;
* выручка;
* средняя цена;
* популярность.

## Employees

* заказов;
* работ;
* сумма;
* загрузка.

## Inventory

* остатки;
* low stock;
* движения;
* расходуемые позиции.

---

# 47. Уведомления

Внутри приложения:

* новая запись;
* изменение записи;
* новый заказ;
* изменение статуса;
* автомобиль готов;
* заказ просрочен;
* низкий остаток;
* новая оплата;
* задолженность.

Архитектура должна позволить позже подключить:

* email;
* SMS;
* WhatsApp;
* push.

---

# 48. Напоминания клиентам — будущий функционал

После закрытия заказа сохранить:

* дату;
* пробег;
* тип обслуживания;
* услугу.

Позже система сможет рассчитывать:

```text
Следующее обслуживание
↓
Reminder
↓
WhatsApp/SMS
↓
"Пора пройти ТО"
↓
Записаться
```

Это один из будущих механизмов возврата клиентов.

---

# 49. Глобальный поиск

Поиск:

* имя;
* телефон;
* госномер;
* VIN;
* марка;
* модель;
* номер заказа.

Результаты:

```text
КЛИЕНТЫ
АВТОМОБИЛИ
ЗАКАЗЫ
```

Горячая клавиша:

```text
Ctrl + K
```

Debounce:

```text
250–400 ms
```

Поиск должен быть серверным.

---

# 50. Activity Log

Записывать:

* создание;
* изменение;
* удаление/архивирование;
* изменение статуса;
* оплату;
* отмену платежа;
* добавление фото;
* изменение прав;
* приглашение пользователя;
* изменение тарифа;
* блокировку.

Пример:

```text
19.09.2026 16:20

Менеджер изменил заказ #1042

Диагностика → В работе
```

---

# 51. Настройки организации

## Основные данные

* название;
* логотип;
* телефон;
* email;
* адрес;
* timezone;
* валюта;
* часы работы.

## Пользователи

* invite;
* role;
* active/inactive;
* permissions.

## Услуги

CRUD.

## Статусы

CRUD/настройка.

---

# 52. Подписка

Страница должна показывать:

* тариф;
* цена;
* статус;
* дату начала;
* дату окончания;
* trial;
* количество пользователей;
* лимиты.

Пример:

```text
Business
69 900 ₸ / месяц

Пользователи
7 / 10

Статус:
Активен

До:
19.10.2026
```

---

# 53. SaaS Admin

Отдельная зона:

```text
/admin
```

## Dashboard

Показывать:

* количество организаций;
* active;
* trial;
* paid;
* MRR;
* новые регистрации;
* количество пользователей;
* активность.

## Organizations

* компания;
* владелец;
* тариф;
* пользователи;
* регистрация;
* trial;
* статус.

Actions:

* открыть;
* заблокировать;
* разблокировать;
* продлить trial;
* изменить тариф.

---

# 54. Demo

Публичная страница:

```text
/demo
```

Демо-организация:

```text
AutoService Demo
```

Данные:

* 50 клиентов;
* 70 автомобилей;
* 30 заказов;
* 8 сотрудников;
* 20 услуг;
* 30 запчастей;
* платежи;
* расходы;
* история.

Режим:

**read only.**

Banner:

> DEMO MODE — данные доступны только для просмотра.

CTA:

> Создать свой автосервис

---

# 55. Landing Page

Главный экран:

## Hero

### Заголовок

> **Управляйте автосервисом в одной системе**

### Подзаголовок

> Клиенты, автомобили, записи, заказ-наряды, запчасти, оплаты и аналитика — в одном рабочем пространстве.

CTA:

```text
Попробовать бесплатно
Посмотреть демо
```

## Секции

1. проблема;
2. решение;
3. возможности;
4. процесс работы;
5. преимущества;
6. screenshot продукта;
7. тарифы;
8. FAQ;
9. финальный CTA.

---

# 56. Onboarding

После регистрации:

### Шаг 1

Компания.

### Шаг 2

Первая услуга.

### Шаг 3

Первый сотрудник.

### Шаг 4

Первый клиент.

### Шаг 5

Первый автомобиль.

### Шаг 6

Первый заказ.

Прогресс:

```text
1 / 6
```

Onboarding можно пропустить.

---

# 57. Основной пользовательский flow

```text
REGISTER
   ↓
ORGANIZATION
   ↓
ONBOARDING
   ↓
SERVICE
   ↓
EMPLOYEE
   ↓
CLIENT
   ↓
CAR
   ↓
APPOINTMENT
   ↓
ORDER
   ↓
MECHANIC
   ↓
DIAGNOSTICS
   ↓
WORKS
   ↓
PARTS
   ↓
PHOTOS
   ↓
STATUS = READY
   ↓
PAYMENT
   ↓
DELIVERED
   ↓
CLOSED
   ↓
HISTORY
   ↓
FUTURE REMINDER
```

Это основной acceptance flow продукта.

---

# 58. Database

Основные таблицы:

```text
organizations
organization_settings

users
user_invites

clients
client_users

cars

service_categories
services

appointments
appointment_statuses

orders
order_statuses
order_items

order_photos

parts
part_categories
inventory_transactions

payments

expense_categories
expenses

notifications

subscriptions
plans

activity_logs

public_order_tokens
```

---

# 59. organizations

```text
id
name
slug
logo_path
phone
email
address
timezone
currency
status
created_at
updated_at
```

`slug` уникален.

---

# 60. users

```text
id
organization_id nullable
name
email
phone
password
role
status
last_login_at
created_at
updated_at
```

Platform Admin может не иметь `organization_id`.

---

# 61. clients

```text
id
organization_id
name
phone
email
comment
status
created_at
updated_at
```

---

# 62. client_users

Связь клиентского профиля с пользовательским аккаунтом.

```text
id
organization_id
client_id
user_id
created_at
```

Нужна для будущего client portal.

---

# 63. cars

```text
id
organization_id
client_id
make
model
year
plate_number
vin
color
mileage
fuel_type
comment
photo_path
created_at
updated_at
```

---

# 64. services

```text
id
organization_id
category_id
name
description
price
duration_minutes
active
created_at
updated_at
```

---

# 65. appointments

```text
id
organization_id
client_id
car_id
mechanic_id
service_id
start_at
end_at
status
comment
created_by
created_at
updated_at
```

---

# 66. orders

```text
id
organization_id
number
client_id
car_id
mechanic_id
status_id
opened_at
planned_completion_at
completed_at
subtotal
discount
total
paid_total
balance
internal_comment
client_comment
created_by
created_at
updated_at
```

---

# 67. order_items

```text
id
organization_id
order_id
type
service_id nullable
part_id nullable
employee_id nullable
name_snapshot
quantity
unit_price
unit_cost nullable
discount
total
created_at
updated_at
```

`name_snapshot` обязателен для исторической целостности.

Если услуга позже переименована, старый заказ продолжает показывать старое название.

---

# 68. order_photos

```text
id
organization_id
order_id
car_id nullable
path
category
caption
visible_to_client
uploaded_by
created_at
```

---

# 69. parts

```text
id
organization_id
category_id
name
sku
supplier
cost_price
sale_price
stock
min_stock
unit
active
created_at
updated_at
```

---

# 70. inventory_transactions

```text
id
organization_id
part_id
type
quantity
unit_cost
reference_type
reference_id
user_id
comment
created_at
```

---

# 71. payments

```text
id
organization_id
order_id
amount
method
status
comment
received_by
paid_at
created_at
updated_at
```

---

# 72. expenses

```text
id
organization_id
category_id
amount
description
expense_date
created_by
created_at
updated_at
```

---

# 73. notifications

```text
id
organization_id
user_id
type
title
body
data JSON
read_at
created_at
```

---

# 74. subscriptions

```text
id
organization_id
plan
status
trial_ends_at
starts_at
ends_at
max_users
created_at
updated_at
```

---

# 75. activity_logs

```text
id
organization_id nullable
user_id nullable
action
subject_type
subject_id
metadata JSON
ip_address
user_agent
created_at
```

---

# 76. public_order_tokens

```text
id
organization_id
order_id
token_hash
expires_at nullable
revoked_at nullable
created_at
```

В базе хранить hash токена, а не открытый токен.

---

# 77. Индексы

Обязательно индексировать:

```text
organization_id
organization_id + phone
organization_id + plate_number
organization_id + vin
organization_id + status
organization_id + created_at
organization_id + client_id
organization_id + car_id
organization_id + mechanic_id
organization_id + start_at
organization_id + order number
```

Unique constraints должны учитывать tenant scope.

Например:

```text
organization_id + order_number
```

---

# 78. Архитектура Laravel

Не помещать бизнес-логику целиком в Controllers.

Рекомендуемая структура:

```text
app/
├── Models/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
├── Services/
├── Actions/
├── Policies/
├── Events/
├── Listeners/
├── Notifications/
├── Jobs/
└── Support/
```

---

# 79. Actions

Выделить важные операции:

```text
CreateClient
CreateCar
CreateAppointment
CreateOrder
AddOrderItem
ChangeOrderStatus
RegisterPayment
CancelPayment
ConsumeInventory
CompleteOrder
CreateExpense
InviteEmployee
```

---

# 80. Events

Примеры:

```text
OrderCreated
OrderStatusChanged
PaymentReceived
InventoryLow
AppointmentCreated
OrderOverdue
```

Это позволит потом подключить:

```text
Event
 ↓
Listener
 ↓
WhatsApp / SMS / Email / Push
```

без переписывания основного заказа.

---

# 81. Policies

Policies обязательны для:

* Client;
* Car;
* Appointment;
* Order;
* OrderPhoto;
* Payment;
* Expense;
* Part;
* Employee;
* Subscription;
* Settings.

---

# 82. Frontend архитектура

React-компоненты должны быть разделены:

```text
resources/js/
├── Pages/
├── Components/
├── Layouts/
├── Forms/
├── Features/
├── Hooks/
├── Utils/
└── Types/
```

Бизнес-правила не должны дублироваться в React.

---

# 83. Inertia

Inertia используется как bridge:

```text
Laravel
   ↕
Inertia
   ↕
React
```

Frontend не превращается в полностью отдельный SPA backend без необходимости.

---

# 84. Bklit UI

Bklit UI используется для базовых элементов.

Основные компоненты:

* Button;
* Input;
* Textarea;
* Select;
* Combobox;
* DatePicker;
* Dialog;
* Sheet;
* Dropdown;
* Table;
* Badge;
* Card;
* Tabs;
* Toast;
* Tooltip;
* Avatar;
* Skeleton;
* Pagination;
* Breadcrumb;
* Command;
* FileUpload;
* ConfirmDialog.

Если Bklit UI не предоставляет какой-либо необходимый компонент, допускается создать собственный wrapper, сохраняя единый design system.

---

# 85. Tailwind

Все spacing/color/radius tokens должны быть централизованными.

Не создавать случайные значения в каждом компоненте.

Использовать единый дизайн:

```text
4
8
12
16
20
24
32
40
48
64
```

---

# 86. Typography

Основной шрифт:

* Inter;
* Geist;
* system-ui.

Размеры:

```text
H1   28–32
H2   22–24
H3   18–20
Body 14–15
Small 13
Caption 12
```

Вес:

* 400;
* 500;
* 600;
* 700.

Не злоупотреблять жирным.

---

# 87. Цветовая система

Tokens:

```text
background
surface
surface-muted
border
text
text-muted
accent
success
warning
danger
info
```

Использовать один главный brand accent.

Системные состояния:

```text
success = успешно
warning = внимание
danger = ошибка
info = информация
```

Не использовать цвет как единственный индикатор.

---

# 88. Радиусы

Рекомендуется:

```text
Input        8px
Button       8px
Card        12px
Dialog      12px
Large block 20–24px
```

---

# 89. Тени

Использовать минимально.

Основной визуальный отделитель:

```text
border
background contrast
```

а не сильные тени.

---

# 90. Sidebar

Desktop:

```text
240–260px
```

Collapsed:

```text
~72px
```

Внизу:

* организация;
* avatar;
* имя;
* роль;
* logout.

Collapsed mode должен использовать tooltip.

---

# 91. Topbar

Слева:

* title;
* breadcrumb.

Справа:

* глобальный поиск;
* notifications;
* profile.

Mobile:

* hamburger;
* title;
* notifications.

---

# 92. Dashboard UI

Порядок:

```text
Greeting

KPI

Attention / Problems

Today

Analytics

Activity
```

Логика:

> Сначала состояние бизнеса → потом проблемы → потом действия → потом аналитика.

---

# 93. KPI card

Карточка должна содержать:

* label;
* value;
* optional comparison;
* icon;
* contextual information.

Не делать KPI декоративными.

---

# 94. Status Badge

Формат:

```text
● В работе
● Готов
● Ожидание
```

Badge должен быть компактным.

---

# 95. Forms

Форма:

```text
Label
Input
Helper text
Error
```

Поле должно иметь:

* default;
* focus;
* disabled;
* error;
* success;
* loading.

Высота:

```text
44px
```

---

# 96. Tables

Desktop — table.

Mobile — cards/list.

Не использовать огромные таблицы.

Например Orders:

```text
№
Автомобиль
Клиент
Мастер
Статус
Сумма
```

Остальные действия:

```text
⋯
```

---

# 97. Empty State

Структура:

```text
Icon
Title
Description
CTA
```

Например:

> Пока нет автомобилей
> Добавьте автомобиль клиента, чтобы начать вести историю обслуживания.

---

# 98. Loading

Использовать skeleton:

* Dashboard;
* table;
* profile;
* cards;
* order page.

---

# 99. Error State

Пользователь не должен видеть:

```text
SQLSTATE...
Call stack...
Exception...
```

Вместо этого:

> Не удалось загрузить данные.

CTA:

> Повторить

---

# 100. Toast

Показывать короткие уведомления.

Примеры:

> Клиент создан.

> Оплата 50 000 ₸ добавлена.

> Заказ переведен в «Готов».

Duration:

```text
3–5 секунд
```

---

# 101. Modal

Использовать для:

* подтверждения;
* короткого создания;
* небольших форм.

Большие формы:

* page;
* drawer;
* sheet.

---

# 102. Confirm Dialog

Для:

* удаления;
* отмены платежа;
* удаления фото;
* блокировки пользователя;
* удаления/архива.

Пример:

> Отменить платеж 50 000 ₸?

Кнопки:

```text
Отмена
Отменить платеж
```

---

# 103. Unsaved Changes

Если пользователь заполнил форму и пытается уйти:

> У вас есть несохраненные изменения.

Actions:

```text
Остаться
Выйти без сохранения
```

---

# 104. Search UX

Глобальный поиск открывается через:

```text
Ctrl + K
```

Показывает:

```text
Клиенты
Автомобили
Заказы
```

Результат должен вести сразу в нужную сущность.

---

# 105. Mobile

Продукт должен быть mobile-first по ежедневным рабочим операциям.

Главная нижняя навигация:

```text
Главная
Заказы
Клиенты
Финансы
Еще
```

Основной CTA:

```text
+ Новый заказ
```

---

# 106. Breakpoints

```text
sm   640
md   768
lg  1024
xl  1280
2xl 1536
```

---

# 107. Адаптивность

На mobile:

* sidebar → drawer;
* table → card;
* multi-column → one column;
* large forms → stack;
* KPI → horizontal/stacked;
* order detail → one column.

Не допускать горизонтального overflow.

---

# 108. Анимации

Использовать умеренно.

Duration:

```text
150–250ms
```

Для:

* hover;
* dropdown;
* modal;
* drawer;
* toast;
* drag/drop;
* status transitions.

Учитывать:

```text
prefers-reduced-motion
```

---

# 109. Accessibility

Обязательно:

* keyboard navigation;
* visible focus;
* semantic HTML;
* aria-label;
* достаточный контраст;
* Esc для modal;
* accessible dropdown;
* accessible form errors.

---

# 110. Date / Time

Формат:

```text
19.09.2026
19.09.2026, 16:30
```

Timezone по умолчанию:

```text
Asia/Almaty
```

В интерфейсе дата отображается в timezone организации.

---

# 111. Currency

Основная валюта MVP:

```text
KZT
```

Формат:

```text
85 000 ₸
```

Не:

```text
85000
```

---

# 112. Localization

MVP:

```text
Русский
```

Архитектура должна позволить:

```text
Қазақша
English
```

---

# 113. Security

Обязательно:

* password hashing;
* CSRF;
* XSS protection;
* SQL injection protection;
* input validation;
* rate limiting;
* secure cookies;
* secure sessions;
* authorization;
* tenant isolation;
* HTTPS;
* upload validation;
* audit/activity log.

---

# 114. Tenant Security

Критически важно:

никогда не делать:

```php
Order::find($id)
```

без проверки текущей организации/Policy.

Предпочтительный подход:

```text
Current organization
        ↓
Scoped Query
        ↓
Policy
        ↓
Resource
```

---

# 115. File Security

Разрешить:

```text
jpg
jpeg
png
webp
```

Проверять:

* MIME;
* size;
* extension;
* content.

Запрещать:

```text
php
js
html
exe
```

---

# 116. Soft Delete

Для критических сущностей предпочтительнее:

```text
archive / soft delete
```

а не физическое удаление.

Особенно:

* clients;
* services;
* employees;
* parts.

Финансовые записи не удалять без контролируемого процесса.

---

# 117. Производительность

Обязательно:

* pagination;
* eager loading;
* индексы;
* предотвращение N+1;
* server-side filtering;
* debounce;
* lazy images;
* image compression;
* caching;
* optimized queries.

---

# 118. Pagination

По умолчанию:

```text
25
```

Дополнительно:

```text
50
100
```

---

# 119. Backup

Production:

* ежедневный DB backup;
* backup файлов;
* хранение нескольких копий;
* процедура восстановления.

Проверять не только создание backup, но и возможность восстановления.

---

# 120. Logging

Хранить:

* application errors;
* auth events;
* security events;
* critical business operations;
* activity log.

Позже:

* Sentry;
* uptime monitor;
* performance monitoring.

---

# 121. Системные настройки

Не хранить секреты в git.

Использовать `.env`.

Пример:

```env
APP_NAME=GarageOS
APP_ENV=production
APP_URL=https://GarageOS.kz

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

FILESYSTEM_DISK=local
CACHE_STORE=
SESSION_DRIVER=
QUEUE_CONNECTION=
```

Для MariaDB допустима Laravel MySQL-compatible connection configuration.

---

# 122. Маршруты

## Public

```text
/
 /demo
 /login
 /register
 /forgot-password
 /order/{token}
```

## Application

```text
/dashboard

/clients
/clients/create
/clients/{id}
/clients/{id}/edit

/cars
/cars/create
/cars/{id}
/cars/{id}/edit

/appointments
/appointments/create
/appointments/{id}

/orders
/orders/create
/orders/{id}
/orders/{id}/edit
/orders/kanban

/employees
/services
/parts

/finance
/finance/payments
/finance/expenses

/analytics

/notifications

/settings
/settings/company
/settings/users
/settings/services
/settings/statuses

/subscription
```

## SaaS Admin

```text
/admin
/admin/organizations
/admin/organizations/{id}
/admin/users
/admin/subscriptions
/admin/activity
```

---

# 123. API слой

В MVP отдельный публичный REST API не обязателен.

Но application layer проектировать так, чтобы в будущем добавить:

```text
/api/v1
```

Возможные будущие endpoints:

```text
clients
cars
appointments
orders
payments
services
employees
inventory
```

---

# 124. Business Rules

## Order Total

```text
total =
services +
parts -
discount
```

## Balance

```text
balance =
total -
successful payments
```

## Low Stock

```text
stock <= min_stock
```

создает notification.

## Order Status

Frontend не может самостоятельно изменить статус без backend authorization.

---

# 125. Order lifecycle

Каждая смена статуса должна быть логичной.

Пример:

```text
Новый
↓
Принят
↓
Диагностика
↓
В работе
↓
Ожидание запчастей
↓
В работе
↓
Готов
↓
Выдан
↓
Закрыт
```

---

# 126. Закрытие заказа

При закрытии:

* заказ должен быть завершенным по статусу;
* итоговая сумма пересчитана;
* платежи учтены;
* история автомобиля обновлена;
* activity log добавлен.

Наличие долга не обязательно должно блокировать закрытие, если политика организации это разрешает.

При наличии долга интерфейс должен четко показать:

> Клиент должен 30 000 ₸.

---

# 127. Reservation → Order

Из записи:

```text
Appointment
↓
Customer arrived
↓
Create Order
↓
Appointment linked to Order
```

Не создавать дубликат клиента или автомобиля.

---

# 128. Client → Car relationships

Один клиент:

```text
1 → N cars
```

Один автомобиль:

```text
1 → N orders
```

Один заказ:

```text
1 → N order_items
1 → N photos
1 → N payments
```

---

# 129. Employee relationships

Один сотрудник:

```text
1 → N orders
```

Один заказ может иметь:

* основного мастера;
* исполнителей отдельных работ.

---

# 130. Future: Work bays / posts

После MVP:

```text
Post 1
Post 2
Post 3
Post 4
```

Заказ получает:

* post;
* start time;
* end time.

Это позволит построить планировщик загрузки.

---

# 131. Future: Multi-branch

Архитектура должна позволить:

```text
Organization
│
├── Branch A
├── Branch B
└── Branch C
```

В MVP `branch_id` можно не реализовывать как полноценный модуль.

Но модели не должны проектироваться так, чтобы multi-branch впоследствии потребовал полного переписывания системы.

---

# 132. Future: Client portal

Клиент сможет:

```text
My GarageOS
├── Автомобили
├── Заказы
├── История
├── Фотографии
├── Оплаты
├── Записи
└── Уведомления
```

---

# 133. Future: WhatsApp

В будущем:

```text
GarageOS
   ↓
WhatsApp API
   ↓
Client
```

Сообщения:

* подтверждение записи;
* статус ремонта;
* автомобиль готов;
* ссылка на заказ;
* напоминание;
* следующее обслуживание.

---

# 134. Future: Online booking

Клиент сможет:

```text
Выбрать услугу
↓
Выбрать дату
↓
Выбрать время
↓
Оставить телефон
↓
Получить запись
```

Запись автоматически появляется в календаре.

---

# 135. Future: Payments

В будущем:

* online acquiring;
* invoices;
* payment links;
* automatic subscription billing.

Важно разделить:

```text
GarageOS subscription payments
```

и

```text
Customer service payments
```

Это разные платежные сущности.

---

# 136. Future: Vehicle reminders

Хранить:

* пробег;
* дату последнего ТО;
* тип услуги;
* рекомендуемый интервал.

После этого:

```text
Reminder Engine
↓
Notification
↓
Client
```

---

# 137. Future: AI

AI не входит в MVP.

В будущем возможно:

* автоматическое описание повреждений по фото;
* рекомендации по обслуживанию;
* AI assistant для менеджера;
* анализ повторяющихся проблем;
* генерация текстов клиенту;
* интеллектуальный поиск.

---

# 138. Что НЕ делать в MVP

Не разрабатывать:

* мобильное native app;
* WhatsApp API;
* SMS API;
* online acquiring;
* 1C;
* сложную бухгалтерию;
* GPS;
* AI;
* telematics;
* сложный HR;
* зарплату;
* маркетплейс запчастей;
* государственные интеграции;
* полноценный multi-branch;
* сложный BI;
* полноценный планировщик постов;
* VIN decoder;
* сложную автоматизацию.

---

# 139. MVP

MVP обязательно включает:

### Core

* registration;
* authentication;
* organizations;
* roles;
* dashboard;
* clients;
* cars;
* services;
* employees;
* appointments;
* calendar;
* orders;
* order statuses;
* Kanban;
* works;
* payments;
* balance;
* history;
* photos;
* basic analytics;
* notifications;
* settings;
* subscription;
* SaaS admin;
* demo mode.

### После первого релиза

* inventory;
* parts;
* expenses;
* advanced analytics;
* full client portal;
* automatic communication.

---

# 140. Sprint Plan

## Sprint 1 — Foundation

* Laravel;
* React;
* Inertia;
* Bklit UI;
* Tailwind;
* auth;
* organization;
* roles;
* layout;
* navigation;
* design tokens.

## Sprint 2 — Clients & Cars

* clients;
* cars;
* profiles;
* search;
* history.

## Sprint 3 — Appointments & Orders

* appointments;
* calendar;
* orders;
* services;
* works;
* statuses.

## Sprint 4 — Kanban & Workforce

* Kanban;
* mechanics;
* assignments;
* photos;
* timeline.

## Sprint 5 — Finance & Inventory

* payments;
* balance;
* parts;
* inventory;
* expenses.

## Sprint 6 — Dashboard & Analytics

* KPI;
* charts;
* notifications;
* analytics;
* activity.

## Sprint 7 — SaaS

* trial;
* plans;
* subscription;
* admin;
* demo.

## Sprint 8 — Production

* security;
* tests;
* responsive;
* optimization;
* backups;
* deployment;
* monitoring.

---

# 141. Testing

## Unit

Проверить:

* order totals;
* discount;
* balance;
* payment calculations;
* inventory;
* permissions.

## Feature

Проверить:

* registration;
* login;
* client;
* car;
* appointment;
* order;
* payment;
* status;
* inventory;
* tenant isolation;
* permissions.

## E2E

Основной сценарий:

```text
Register
↓
Create Client
↓
Create Car
↓
Create Appointment
↓
Create Order
↓
Add Service
↓
Assign Mechanic
↓
Change Status
↓
Add Payment
↓
Close Order
↓
View History
```

---

# 142. Definition of Done

Функция считается готовой только если:

* frontend реализован;
* backend реализован;
* migration готова;
* validation работает;
* authorization работает;
* tenant isolation работает;
* loading state есть;
* empty state есть;
* error state есть;
* success feedback есть;
* desktop работает;
* mobile работает;
* keyboard navigation работает;
* критические действия логируются;
* нет console errors;
* критическая бизнес-логика покрыта тестами.

---

# 143. UX checklist

Каждая страница должна иметь:

```text
Normal
Loading
Empty
Error
Success
Validation Error
Disabled
Permission Denied
```

Каждое основное действие:

```text
Action
↓
Loading
↓
Success / Error
```

Пользователь всегда должен понимать, произошло ли действие.

---

# 144. Главный принцип интерфейса

GarageOS не должен заставлять владельца разбираться в системе.

Пользователь должен видеть:

```text
Что происходит?
↓
Что важно?
↓
Что делать?
```

---

# 145. Основные ежедневные действия

Они должны выполняться максимально быстро.

## Новый клиент

```text
+ Клиент
↓
Имя + телефон
↓
Сохранить
```

## Новый автомобиль

```text
Клиент
↓
+ Автомобиль
↓
Марка + модель + номер
↓
Сохранить
```

## Новый заказ

```text
+ Заказ
↓
Клиент
↓
Автомобиль
↓
Сохранить
```

## Оплата

```text
Заказ
↓
+ Оплата
↓
Сумма + метод
↓
Сохранить
```

---

# 146. Главная бизнес-логика продукта

GarageOS должен сокращать:

* ручную работу;
* потерю клиентов;
* забытые записи;
* потерянные заказы;
* ошибки в расчетах;
* потерю истории;
* отсутствие контроля сотрудников;
* неясность по долгам;
* отсутствие контроля выручки.

И увеличивать:

* прозрачность;
* скорость работы;
* возврат клиентов;
* средний чек;
* контроль бизнеса.

---

# 147. Главная концепция данных

Связь:

```text
ORGANIZATION
      │
      ├── USERS
      │     ├── OWNER
      │     ├── MANAGER
      │     └── MECHANIC
      │
      ├── CLIENTS
      │     │
      │     └── CARS
      │           │
      │           └── ORDERS
      │                 ├── SERVICES
      │                 ├── PARTS
      │                 ├── PHOTOS
      │                 ├── PAYMENTS
      │                 └── ACTIVITY
      │
      ├── APPOINTMENTS
      ├── EXPENSES
      ├── INVENTORY
      ├── NOTIFICATIONS
      └── SUBSCRIPTION
```

---

# 148. Главный рабочий объект

Центральный объект:

```text
Order
```

Он связывает:

```text
Client
   +
Car
   +
Employee
   +
Services
   +
Parts
   +
Photos
   +
Payment
   +
Status
   +
History
```

Поэтому архитектура должна строиться вокруг Order Lifecycle.

---

# 149. Product Roadmap

## Phase 1

**MVP**

Core CRM + Orders + Appointments + Payments.

## Phase 2

**Operations**

Inventory + Expenses + Advanced Analytics.

## Phase 3

**Customer Retention**

Client portal + WhatsApp + reminders + online booking.

## Phase 4

**Scale**

Multi-branch + API + integrations + advanced reporting.

## Phase 5

**Automation**

AI + advanced workflow engine + intelligent recommendations.

---

# 150. Финальная позиция продукта

GarageOS должен продаваться не как:

> «CRM для СТО».

А как:

> **«Операционная система для автосервиса».**

Пользователь должен воспринимать продукт как единую рабочую среду:

```text
Клиенты
+
Автомобили
+
Записи
+
Заказы
+
Мастера
+
Услуги
+
Запчасти
+
Склад
+
Оплаты
+
Расходы
+
Аналитика
+
История
+
Уведомления
+
Возврат клиентов
=
GarageOS
```

---

# 151. Главный критерий готовности MVP

Новый автосервис должен самостоятельно пройти:

```text
Регистрация
      ↓
Создание компании
      ↓
Добавление услуги
      ↓
Добавление сотрудника
      ↓
Добавление клиента
      ↓
Добавление автомобиля
      ↓
Создание записи
      ↓
Создание заказа
      ↓
Назначение мастера
      ↓
Добавление работ
      ↓
Изменение статуса
      ↓
Фотографии
      ↓
Оплата
      ↓
Выдача автомобиля
      ↓
Закрытие заказа
      ↓
История автомобиля
      ↓
Dashboard
```

без помощи разработчика.

---

# 152. Финальное техническое решение

## Frontend

**React + Inertia.js + Tailwind CSS + Bklit UI**

## Backend

**PHP 8.3+ + Laravel 12**

## Database

**MariaDB**, с возможностью использования MySQL 8.

## Server

**Nginx / Apache + PHP-FPM**

## Storage

**Laravel Storage**, затем S3-compatible.

## Architecture

**Multi-tenant SaaS**

## Roles

```text
Platform Admin
Owner
Manager
Mechanic
Client
```

## MVP focus

```text
СТО / Детейлинг / Шиномонтаж /
Ходовка / Моторы / Масло /
Электрика / Диагностика /
Кузов / Мойка
```

---

# 153. Итог

GarageOS начинается как специализированная SaaS-платформа для автомобильных сервисов.

Архитектура при этом строится модульно:

```text
GarageOS Core
      +
Auto Service Modules
```

Это позволяет в будущем расширять продукт внутри автомобильной индустрии и добавлять новые модули без переписывания ядра.

Но первая версия сознательно остается сфокусированной:

> **Сделать лучший рабочий инструмент для ежедневной операционной работы небольшого и среднего автосервиса.**
