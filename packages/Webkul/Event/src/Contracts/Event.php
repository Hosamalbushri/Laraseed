<?php

namespace Webkul\Event\Contracts;

/**
 * Event contract. Implementing models use fillable:
 * title, event_date, event_end_date, organizer, available_seats,
 * availability_use_seats, availability_use_end_date, image, description, status.
 * categories via pivot event_event_category.
 */
interface Event {}
