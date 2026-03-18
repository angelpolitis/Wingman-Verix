<?php
    /**
     * Project Name:    Wingman Verix - Verix Exception
     * Created by:      Angel Politis
     * Creation Date:   Mar 17 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Exceptions namespace.
    namespace Wingman\Verix\Exceptions;

    /**
     * A marker interface for all Verix exceptions; allows consumers to catch any
     * Verix-thrown exception in a single catch clause regardless of the base type.
     * @package Wingman\Verix\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    interface VerixException {}
?>