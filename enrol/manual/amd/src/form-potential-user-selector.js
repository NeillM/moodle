// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Potential user selector module.
 *
 * @module     enrol_manual/form-potential-user-selector
 * @class      form-potential-user-selector
 * @package    enrol_manual
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates', 'core/str'], function($, Ajax, Templates, Str) {

    /** @var {Number} Maximum number of users to show. */
    var MAXUSERS = 100;

    /** @var {Number} Delay in mili-seconds before an AJAX request is sent. */
    var AJAXDELAY = 500;

    /** @type {Boolean} Stores if a job is executing. */
    var executing = false;

    /** @var {Number} Stores the identifier of the function that contains the delayed AJAX call.  */
    var timeoutId;

    /** @var {String} Stores the last query that has not been sent to Moodle. */
    var lastquery;

    /**
     * Controls sending AJAX queries to find potential users to Moodle.
     *
     * It delays the query until the user pauses in their typing, it also ensures that a
     * new request is never sent to Moodle until the previous request has completed.
     *
     * @param {String} selector
     * @param {String} query
     * @param {Function} success
     * @param {Function} failure
     * @returns {undefined}
     */
    var transport = function(selector, query, success, failure) {
        if (!executing && typeof timeoutId !== "undefined") {
            // There is an AJAX request queued to go, stop it since the data we want has changed.
            clearTimeout(timeoutId);
        } else if (executing) {
            // There is an AJAX request to Moodle in progress. We should not queue another request
            // right now, as we do not know how long it may take to complete. We will store the query
            // so that it can be run after it completes.
            lastquery = query;
            return;
        }

        var courseid = $(selector).attr('courseid');
        if (typeof courseid === "undefined") {
            courseid = '1';
        }
        var enrolid = $(selector).attr('enrolid');
        if (typeof enrolid === "undefined") {
            enrolid = '';
        }

        timeoutId = setTimeout(function() {
            // We store that the call has been sent.
            executing = true;
            // This code will be exectuted after the AJAXDELAY time has passed, if it is not interupeted.
            var promise = Ajax.call([{
                methodname: 'core_enrol_get_potential_users',
                args: {
                    courseid: courseid,
                    enrolid: enrolid,
                    search: query,
                    searchanywhere: true,
                    page: 0,
                    perpage: MAXUSERS + 1
                }
            }]);

            promise[0].then(function(results) {
                var promises = [],
                    i = 0;

                if (results.length <= MAXUSERS) {
                    // Render the label.
                    $.each(results, function(index, user) {
                        var ctx = user,
                            identity = [];
                        $.each(['idnumber', 'email', 'phone1', 'phone2', 'department', 'institution'], function(i, k) {
                            if (typeof user[k] !== 'undefined' && user[k] !== '') {
                                ctx.hasidentity = true;
                                identity.push(user[k]);
                            }
                        });
                        ctx.identity = identity.join(', ');
                        promises.push(Templates.render('enrol_manual/form-user-selector-suggestion', ctx));
                    });

                    // Apply the label to the results.
                    return $.when.apply($.when, promises).then(function() {
                        var args = arguments;
                        $.each(results, function(index, user) {
                            user._label = args[i];
                            i++;
                        });
                        success(results);
                        return;
                    });

                } else {
                    return Str.get_string('toomanyuserstoshow', 'core', '>' + MAXUSERS).then(function(toomanyuserstoshow) {
                        success(toomanyuserstoshow);
                        return;
                    });
                }

            }).fail(failure).done(function() {
                executing = false;
                if (typeof lastquery !== "undefined") {
                    query = lastquery;
                    lastquery = undefined;
                    // There is a query waiting to be processed, so send it using the normal rules.
                    // This will allow it to be interupted if the user is still typing.
                    transport(selector, query, success, failure);
                }
            });
        }, AJAXDELAY);
    };

    return /** @alias module:enrol_manual/form-potential-user-selector */ {

        processResults: function(selector, results) {
            var users = [];
            if ($.isArray(results)) {
                $.each(results, function(index, user) {
                    users.push({
                        value: user.id,
                        label: user._label
                    });
                });
                return users;

            } else {
                return results;
            }
        },

        transport: transport
    };

});
