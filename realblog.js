/*!
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2017 Christoph M. Becker
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

/**
 * @namespace
 */
var REALBLOG = REALBLOG || {};

/**
 * Initializes the date pickers.
 */
REALBLOG.initDatePickers = function () {
    var i, datePicker;

    for (i = 1; i <= 3; i++) {
        datePicker = document.getElementById("realblog_trig_date" + i);
        if (datePicker) {
            if (this.hasNativeDatePicker) {
                datePicker.style.display = "none";
            } else {
                document.getElementById("realblog_date" + i).onfocus = function () {
                    this.blur();
                };
                Calendar.setup({
                    inputField: "realblog_date" + i,
                    ifFormat: "%Y-%m-%d",
                    button: "realblog_trig_date" + i,
                    align: "Br",
                    singleClick: true,
                    firstDay: 1,
                    weekNumbers: false,
                    electric: false,
                    showsTime: false,
                    timeFormat: "24"
                });
            }
        }
    }
}

REALBLOG.initDatePickers();

(function () {
    var select, i, option;

    if (REALBLOG.categories) {
        select = document.getElementById("realblog_category_select");
        if (select) {
            for (i = 0; i < REALBLOG.categories.length; i++) {
                option = document.createElement("option");
                option.text = option.value = REALBLOG.categories[i];
                select.add(option);
            }
            select.onchange = function (event) {
                var target, input, category;
    
                event = event || window.event;
                target = event.target || event.srcElement;
                input = document.getElementById("realblog_categories");
                if (input && target.selectedIndex) {
                    category = target[target.selectedIndex].value;
                    if (input.value) {
                        input.value += "," + category;
                    } else {
                        input.value = category;
                    }
                }
                target.selectedIndex = 0;
            };
        }
    }
}());
