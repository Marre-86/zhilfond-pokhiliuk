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

Необходимо реализовать каркас сервиса уведомлений на Laravel. Полный текст ТЗ <a href="https://docs.google.com/document/d/1T-GaADiMze5WngG8lbjK2K8P2bkc67zGiaoH8wj5lXM/edit?tab=t.0" target="_blank">здесь</a>.

## Решение

При проектировании сервиса уведомлений в качестве базового был выбран стандартный паттерн <a href="https://refactoring.guru/ru/design-patterns/strategy" target="_blank">"Стратегия"</a>, позволяющий легко расширять сервис уведомлений новыми каналами без изменений существующего кода. Необходимо лишь добавить новый канал (или "стратегию" ) в качестве нового класса в App\Notifications\Strategies и добавить строчку с этим новым классом в мэппинг в config/notifications.php.

Каждая стратегия имплементирует интерфейс App\Contracts\NotificationStrategy и реализует обязательный метод send() - реальной реализации нет, согласно ТЗ, поставлена заглушка.

Написана тестовая консольная команда app/Console/Commands/TestNotificationCommand.php, демонстрирующая практическое использование сервиса уведомлений.