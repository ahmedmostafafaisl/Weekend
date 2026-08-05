<?php

namespace App\Models;

/**
 * @deprecated Consolidated into UniteDetail as part of the venue-detail
 * model refactor (see migration 2026_06_19_000003_create_unite_details_table).
 * This class is kept only so any code still type-hinting or instantiating
 * CampDetail directly does not hard-crash — it now transparently extends
 * UniteDetail and reads/writes the same unite_details table.
 *
 * New code should use App\Models\UniteDetail directly. This alias will be
 * removed in a future cleanup once confirmed nothing references it.
 */
class CampDetail extends UniteDetail {}
