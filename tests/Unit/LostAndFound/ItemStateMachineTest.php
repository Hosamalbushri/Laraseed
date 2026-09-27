<?php

namespace Tests\Unit\LostAndFound;

use InvalidArgumentException;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Services\ItemStateService;

class ItemStateMachineTest extends TestCase
{
    public function test_valid_item_state_transitions(): void
    {
        $this->assertTrue(ItemStateService::canTransition(ItemStatus::DRAFT, ItemStatus::REPORTED));
        $this->assertTrue(ItemStateService::canTransition(ItemStatus::REPORTED, ItemStatus::IN_CUSTODY));
        $this->assertTrue(ItemStateService::canTransition(ItemStatus::IN_CUSTODY, ItemStatus::RETURNED));
    }

    public function test_invalid_item_state_transitions_throw_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ItemStateService::transition(ItemStatus::RETURNED, ItemStatus::IN_CUSTODY);
    }

    public function test_terminal_states_cannot_transition(): void
    {
        $this->assertFalse(ItemStateService::canTransition(ItemStatus::RETURNED, ItemStatus::REPORTED));
        $this->assertFalse(ItemStateService::canTransition(ItemStatus::DISPOSED, ItemStatus::IN_CUSTODY));
    }
}
