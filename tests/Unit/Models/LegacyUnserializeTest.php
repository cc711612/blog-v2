<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Tests\TestCase;

/** 舊資料的序列化欄位不可還原成物件（PHP 物件注入）。 */
class LegacyUnserializeTest extends TestCase
{
    public static bool $gadgetWokeUp = false;

    /**
     * @return array<string, array{0: callable(string): array}>
     */
    public static function decoders(): array
    {
        return [
            'Article::seo' => [fn (string $v) => (new Article)->getSeoAttribute($v)],
            'Comment::logs' => [fn (string $v) => (new Comment)->getLogsAttribute($v)],
            'User::images' => [fn (string $v) => (new User)->getImagesAttribute($v)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('decoders')]
    public function test_serialized_objects_are_never_instantiated(callable $decode): void
    {
        self::$gadgetWokeUp = false;
        $payload = serialize(new LegacyUnserializeGadget);

        $this->assertSame([], $decode($payload));
        $this->assertFalse(self::$gadgetWokeUp, 'unserialize() instantiated an object');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('decoders')]
    public function test_serialized_arrays_and_json_still_decode(callable $decode): void
    {
        $this->assertSame(['a' => 1, 'b' => [2, 3]], $decode(serialize(['a' => 1, 'b' => [2, 3]])));
        $this->assertSame(['a' => 1], $decode('{"a":1}'));
        $this->assertSame([], $decode(''));
        $this->assertSame([], $decode('not serialized at all'));
    }
}

class LegacyUnserializeGadget
{
    public function __wakeup(): void
    {
        LegacyUnserializeTest::$gadgetWokeUp = true;
    }
}
