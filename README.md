<h1 align="center">Тестовое задание для АН «Жилфонд» <a href="https://jilfond.ru/" target="_blank"><img src="./public/images/zhilfond-logo.png" alt="AmoPoint Logo" width="100"></a></h1>
<h3 align="center">Кандидат: Похилюк Артем</h3>
<h4 align="center">Вакансия: Backend-разработчик (middle)</h4>


<p align="center">
  <a href="https://hh.ru/vacancy/132510661" target="_blank">
    <img src="https://img.shields.io/badge/вакансия-007bff?style=for-the-badge" alt="Вакансия">
  </a>
  &nbsp;
  <a href="https://hh.ru/resume/9207f557ff0bf4ecfc0039ed1f71464d546442" target="_blank">
    <img src="https://img.shields.io/badge/резюме_кандидата-28a745?style=for-the-badge" alt="Резюме кандидата">
  </a>
</p>

## Описание задания

Необходимо реализовать каркас сервиса уведомлений на Laravel. Полный текст ТЗ [здесь](testovoe.docx)
.

## Решение

При проектировании сервиса уведомлений в качестве базового был выбран стандартный паттерн <a href="https://refactoring.guru/ru/design-patterns/strategy" target="_blank">"Стратегия"</a>, позволяющий легко расширять сервис уведомлений новыми каналами без изменений существующего кода. Необходимо лишь добавить новый канал (или "стратегию" ) в качестве нового класса в [App\Notifications\Strategies](app/Notifications/Strategies/) и добавить строчку с этим новым классом в мэппинг в [config/notifications.php](config/notifications.php), а новый канал - в Enum [App\Enums\NotificationChannel](app/Enums/NotificationChannel.php).

Каждая стратегия имплементирует интерфейс [App\Contracts\NotificationStrategy](app/Contracts/NotificationStrategy.php) и реализует обязательный метод send() - реальной реализации нет, согласно ТЗ, поставлена заглушка. Заглушка представляет из себя симуляцию обращения во внешний сервис (отправки уведомления) и рандомно возвращающую "успех" или "неудачу" (вероятности этого, а также другие настройки данной заглушки можно подкрутить в config/notifications.php).

[App\Notifications\NotificationService](app/Notifications/NotificationService.php) - сервис уведомлений, в котором можно установить выбранную стратегию и осуществить отправку.

Написана тестовая консольная команда [App\Console\Commands\TestNotificationCommand](app/Console/Commands/TestNotificationCommand.php), демонстрирующая практическое использование сервиса уведомлений. Команда выполняет создание и отправку уведомления. Создание уведомления (но не отправку) также можно сделать путем обращения извне на API-route **POST /api/store-notification**. Чтобы избежать дублирования, переиспользуемый код вынесен в отдельный сервис [App\Services\NotificationCreator](app/Services/NotificationCreator.php).

Реализовано также ещё два эндпойнта API:
- **GET /notification-status/:id** - проверка статуса оповещения по id
- **GET /user/:id/notifications** - возвращает список всех оповещений указанного пользователя. Есть фильтрация по статусу и каналу.

### Гарантия доставки
Ключевой метод стратегий send() возвращает объект класса [App\Notifications\SendResult](app/Notifications/Strategies/EmailNotificationStrategy.php), который содержит данные о результатах попытки отправки уведомления. **NotificationService** принимает их и, в случае если получил неудачу, выполняет заданные действия по повторной отправке. На данный момент эти действия по сути не заданы - см. следующий пункт "Что можно улучшить для продакшна".

### Что можно улучшить для продакшна
- Создать отдельный Job-класс **SendNotificationJob**, который будет отвечать за повторные попытки отправки уведомлений. Использовать этот Job-класс в методе *scheduleRetry()* сервиса уведомлений **NotificationService** (вместо простого логирования, которое прописано там сейчас).
- Запроектировать использование Job-класса **SendNotificationJob** для отправки уведомлений, полученных извне на API-эндпойнт **POST /api/store-notification** (сейчас они просто сохраняются в БД). Для этого придётся либо расширить эндпойнт, чтобы он принимал необходимые для отправки параметры (email, telegram_chat_id), либо извлекать эти значения из БД (предусмотреть сохранения данных параметров для пользователей).

### Тесты, статический анализ, code style
- Code style проверяется командой `make lint` - в качестве линтера выбран [PHP_CodeSniffer](https://github.com/squizlabs/php_codesniffer).
- Статический анализ кода PHPSTan проверяется командой `make phpstan`.
- Автотесты написаны, запускаются командой `make test`, покры
- Возможен запуск вышеперечисленных инструментов одной командой - `make check`.


## Запуск проекта через Docker

Необходимо наличие предустановленного Docker и Docker Compose.

1. **Склонируйте репозиторий и перейдите в каталог с ним**:
   ```bash
   git clone https://github.com/Marre-86/zhilfond-pokhiliuk.git zhilfond-pokhiliuk
   cd zhilfond-pokhiliuk
   ```

1. **Скопируйте файл окружения** (если его нет):
   ```bash
   cp .env.example .env
   ```

3. **Запустите контейнеры**:
   ```bash
   docker compose up -d
   ```

4. **Установите зависимости PHP** внутри контейнера:
   ```bash
   docker compose exec app composer install
   ```

5. **Сгенерируйте ключ приложения**:
   ```bash
   docker compose exec app php artisan key:generate
   ```

6. **Выполните миграции и сидирование базы данных**:
   ```bash
   docker compose exec app php artisan migrate --seed
   ```

7. **Приложение будет доступно** по адресу: http://localhost

### Описание сервисов

Docker Compose включает следующие сервисы:

- **app**: PHP-FPM 8.3 с Xdebug для покрытия кода
- **web**: Nginx веб-сервер
- **database**: MySQL 8.4.9

### Полезные команды

- **Просмотр состояние контейнеров**: `docker compose ps`
- **Просмотр логов**: `docker compose logs -f`
- **Остановка контейнеров**: `docker compose down`
- **Подключение к консоли БД**:
```bash
   docker compose exec database bash
   mysql -ularavel -ppass zhilfond_pokhiliuk
   ```
- **Подключение к консоли приложения**: `docker compose exec app bash`

изнутри консоли приложения:
- **Запуск тестов**: `make test`
- **Проверка PHPStan**: `make phpstan`
- **Проверка code style**: `make lint`
- **Линтер, PHPStan, тесты одной командой**: `make check`
- **Запуск тестовой команды уведомлений**: `php artisan notify:test email "Тестовое сообщение" '{"email":"test@example.com"}'`

