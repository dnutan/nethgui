<?php
namespace Nethgui\Utility;

/*
 * Copyright (C) 2012 Nethesis S.r.l.
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Objects that store data into the session should implement this interface
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
interface SessionConsumerInterface
{
    /**
     * @return SessionConsumerInterface The object itself
     */
    public function setSession(\Nethgui\Utility\SessionInterface $session);
}
