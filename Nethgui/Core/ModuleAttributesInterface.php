<?php
namespace Nethgui\Core;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
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
 * All values returned from these operations are invariants.
 */
interface ModuleAttributesInterface
{
    /**
     * Gets the Module `title` attribute.
     * @return string
     */
    public function getTitle();

    /**
     * Gets the Module `description` attribute.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Get module tags for search implementation.
     *
     * Any composite module must take care of getTags children's call.
     *
     * @return string
     */
    public function getTags();

    /**
     * @return string Unique parent module identifier
     */
    public function getCategory();
    
    /**
     * @return string
     */
    public function getMenuPosition();

    /**
     * The name of the language catalog where to search the translated strings
     * 
     * @return string|array The language catalog name, or catalog name list
     */
    public function getLanguageCatalog();
}


