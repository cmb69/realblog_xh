/*!
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2023 Christoph M. Becker
 *
 * This file is part of Realblog_XH.
 *
 * Realblog_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Realblog_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Realblog_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

(function () {
    let meta = document.getElementsByTagName("meta").realblog;
    if (!meta) return;
    let categories = JSON.parse(meta.content);
    if (!categories) return;
    let select = document.getElementById("realblog_category_select");
    if (!select) return
    for (let i = 0; i < categories.length; i++) {
        let option = document.createElement("option");
        option.text = option.value = categories[i];
        select.add(option);
    }
    select.onchange = function (event) {
        let input = document.getElementById("realblog_categories");
        let selectedIndex = event.target.selectedIndex;
        event.target.selectedIndex = 0;
        if (!input || !selectedIndex) return;
        let category = event.target[selectedIndex].value;
        if (input.value) {
            input.value += "," + category;
        } else {
            input.value = category;
        }
    };
}());
