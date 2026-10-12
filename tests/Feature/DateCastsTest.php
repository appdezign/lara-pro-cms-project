<?php

namespace Tests\Feature;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Lara\App\Models\Event;
use Lara\Common\Casts\DateCast;
use Lara\Common\Casts\DateTimeCast;
use Lara\Common\Casts\TimeCast;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The date casts of the front: dates are stored as Y-m-d and read as "j M Y",
 * times are read as a Carbon instance. Event uses DateCast (startdate, enddate)
 * and TimeCast (starttime, endtime); DateTimeCast is not used by any model.
 */
class DateCastsTest extends TestCase
{
    public function test_a_date_is_stored_as_a_date_and_read_for_display(): void
    {
        $event = Event::make(['startdate' => '2026-12-31']);

        $this->assertSame('2026-12-31', $event->getAttributes()['startdate']);
        $this->assertSame('31 Dec 2026', $event->startdate);
    }

    public function test_a_displayed_date_can_be_stored_again(): void
    {
        $event = Event::make(['startdate' => '31 Dec 2026']);

        $this->assertSame('2026-12-31', $event->getAttributes()['startdate']);
    }

    public function test_a_time_is_read_as_a_time(): void
    {
        $event = Event::make(['starttime' => '14:30']);

        $this->assertSame('14:30', $event->starttime->format('H:i'));
    }

    /**
     * @return array<string, array{class-string<CastsAttributes>, string|null}>
     */
    public static function emptyValues(): array
    {
        $cases = [];

        foreach ([DateCast::class, DateTimeCast::class, TimeCast::class] as $cast) {
            $cases[class_basename($cast).', null'] = [$cast, null];
            $cases[class_basename($cast).', empty string'] = [$cast, ''];
        }

        return $cases;
    }

    /**
     * Carbon::parse(null) is "now": an empty optional date must not become today.
     */
    #[DataProvider('emptyValues')]
    public function test_an_empty_value_stays_empty(string $castClass, ?string $value): void
    {
        $cast = new $castClass;
        $model = new class extends Model {};

        $this->assertNull($cast->set($model, 'moment', $value, []));
        $this->assertNull($cast->get($model, 'moment', $value, []));
    }

    public function test_an_event_without_an_end_date_has_no_end_date(): void
    {
        $event = Event::make(['startdate' => '2026-12-31', 'enddate' => null]);

        $this->assertNull($event->getAttributes()['enddate']);
        $this->assertNull($event->enddate);
    }

    public function test_a_date_and_time_is_stored_with_seconds_and_read_for_display(): void
    {
        $cast = new DateTimeCast;
        $model = new class extends Model {};

        $stored = $cast->set($model, 'moment', '2026-12-31 14:30', []);

        $this->assertSame('2026-12-31 14:30:00', $stored);
        $this->assertSame('31 Dec 2026 14:30', $cast->get($model, 'moment', $stored, []));
    }
}
