<?php

namespace Webkul\LostAndFound\Enums;

enum EvidenceType: string
{
    case TEXT_DESCRIPTION = 'text_description';
    case IMAGE_ATTACHMENT = 'image_attachment';
    case PURCHASE_RECEIPT = 'purchase_receipt';
    case MARKING_DETAIL = 'marking_detail';
    case SERIAL_FRAGMENT = 'serial_fragment';
    case CONTAINER_DETAILS = 'container_details';
}
