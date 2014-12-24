// ** I18N
/* utf8-marker = äöüß */
// Calendar EN language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Encoding: any
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("Nedeľa",
 "Pondelok",
 "Utorok",
 "Streda",
 "Štvrok",
 "Piatok",
 "Sobota",
 "Nedeľa");

// Please note that the following array of short day names (and the same goes
// for short month names, _SMN) isn't absolutely necessary.  We give it here
// for exemplification on how one can customize the short day names, but if
// they are simply the first N letters of the full name you can simply say:
//
//   Calendar._SDN_len = N; // short day name length
//   Calendar._SMN_len = N; // short month name length
//
// If N = 3 then this is not needed either since we assume a value of 3 if not
// present, to be compatible with translation files that were written before
// this feature.

// short day names
Calendar._SDN = new Array
("Ne",
 "Po",
 "Ut",
 "St",
 "Št",
 "Pi",
 "So",
 "Ne");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 1;

// full month names
Calendar._MN = new Array
("Janu8r",
 "Febru8r",
 "Marec",
 "Apríl",
 "Máj",
 "Jún",
 "Júl",
 "August",
 "September",
 "Október",
 "November",
 "December");

// short month names
Calendar._SMN = new Array
("Jan",
 "Feb",
 "Mar",
 "Apr",
 "May",
 "Jún",
 "Júl",
 "Aug",
 "Sep",
 "Okt",
 "Nov",
 "Dec");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "O kalendári";

Calendar._TT["ABOUT"] =
"DHTML Date/Time Selektor\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"Najnovšiu verziu nájdete: http://www.dynarch.com/projects/calendar/\n" +
"Šírené podľa licencei GNU LGPL.  Viac na http://gnu.org/licenses/lgpl.html." +
"\n\n" +
"Výber dátumu:\n" +
"- použite \xab, \xbb tlačidlo pre výber roku\n" +
"- použite " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " tlačidlo pre výber mesiaca\n" +
"- podržte niektoré tlačidlo pre rýchlejší výber.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Výber času:\n" +
"- kliknutím na časť časového údaja zvýšite jeho hodnotu\n" +
"- pomocou Shift-klik hodnotu znížite it\n" +
"- kliknutím a ťahom nastavíte rýchlejšie.";

Calendar._TT["PREV_YEAR"]  = "Predch. rok (podržaním menu)";
Calendar._TT["PREV_MONTH"] = "Predch. mesiac (podrržaním menu)";
Calendar._TT["GO_TODAY"]   = "Dnes";
Calendar._TT["NEXT_MONTH"] = "Nasl. mesiac (podržaním menu)";
Calendar._TT["NEXT_YEAR"]  = "Nasl. rok (podržaním menu)";
Calendar._TT["SEL_DATE"]   = "Vyberte dátum";
Calendar._TT["DRAG_TO_MOVE"] = "Ťahaním presunúť";
Calendar._TT["PART_TODAY"] = " (dnes)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Zobraz %s ako prvý";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "1,6";

Calendar._TT["CLOSE"] = "Zatvoriť";
Calendar._TT["TODAY"] = "Dnes";
Calendar._TT["TIME_PART"] = "(Shift-)Klik alebo ťahom zmeniť hodnotu";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%d.%m.%Y";
Calendar._TT["TT_DATE_FORMAT"] = "%a, %b %e";

Calendar._TT["WK"] = "týž";
Calendar._TT["TIME"] = "Čas:";
