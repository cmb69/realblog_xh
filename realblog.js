/*!
 * Client side scripting of Realblog_XH
 *
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
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
