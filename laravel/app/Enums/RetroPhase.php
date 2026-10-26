<?php

namespace App\Enums;

enum RetroPhase: string
{
    case Draft = 'draft';
    case Collect = 'collect';
    case Vote = 'vote';
    case Discuss = 'discuss';
    case Done = 'done';

    /** Порядок прохождения ретро. */
    public static function flow(): array
    {
        return [self::Draft, self::Collect, self::Vote, self::Discuss, self::Done];
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft   => 'Подготовка',
            self::Collect => 'Сбор карточек',
            self::Vote    => 'Голосование',
            self::Discuss => 'Обсуждение',
            self::Done    => 'Завершено',
        };
    }

    /** Индекс активности: меньше = «живее» ретро (для сортировок). */
    public function activity(): int
    {
        return array_search($this, self::flow(), true);
    }

    public function next(): ?self
    {
        return self::flow()[$this->activity() + 1] ?? null;
    }

    public function isDiscussion(): bool
    {
        return in_array($this, [self::Discuss, self::Done], true);
    }
}
