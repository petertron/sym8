var totalEntries;
var currentRow;
var sectionID;
var uniqueAction;
var uniqueField;
var batchSize;
var importURL;
var startTime;
var fieldIDs;


jQuery(function($){
    // Window resize function (for adjusting the height of the console):
    $(window).resize(function(){
        $("div.console").height($(window).height() - 350);
    }).resize();

    put('Initializing...');

    totalEntries = getVar('total-entries');
    sectionID    = getVar('section-id');
    uniqueAction = getVar('unique-action');
    uniqueField  = getVar('unique-field');
    batchSize    = getVar('batch-size');
    importURL    = getVar('import-url');
    fieldIDs     = getVar('field-ids');

    put('Start import of ' + totalEntries + ' entries; section ID: ' + sectionID + '; unique action: ' + uniqueAction);

    if(totalEntries > 0)
    {
        startTime = new Date();
        importRows(0);
    }

});

/**
 * Import batchSize rows at the time
 * @param nr
 */
function importRows(nr)
{
    currentRow = nr;
    var fields = {};
    fields.ajax = 1;
    fields['unique-action'] = uniqueAction;
    fields['unique-field'] = uniqueField;
    fields['batch-size'] = batchSize;
    fields['section-id'] = sectionID;
    fields['row'] = currentRow;
    fields['field-ids'] = fieldIDs;
    fields['xsrf'] = Symphony.Utilities.getXSRF();
    jQuery.ajax({
        url: importURL,
        async: true,
        type: 'post',
        cache: false,
        data: fields,
        success: function(data, textStatus){
            c = data.substring(0, 4) == '[OK]' ? null : 'error';
            till = ((currentRow + 1) * batchSize) <= totalEntries ? ((currentRow + 1) * batchSize) : totalEntries;
            put('Import entries ' + ((currentRow * batchSize) + 1) + ' - ' + till  + ' : ' + data, c);
            jQuery("div.progress div.bar").css({width: (((currentRow * batchSize) / totalEntries) * 100) + '%'});
            jQuery("div.progress div.bar").attr({'aria-valuenow': Math.round(((currentRow * batchSize) / totalEntries) * 100)});
            elapsedTime = new Date();
            ms = elapsedTime.getTime() - startTime.getTime();
            e = time(ms);
            p = ((currentRow * batchSize) / totalEntries);
            eta = time((ms * (1/p)) - ms);

            jQuery("div.progress div.bar").text('Elapsed time: ' + e + ' / Estimated time left: ' + eta);

            // Check if the next entry should be imported:
            if(((currentRow + 1) * batchSize) < totalEntries)
            {
                importRows(currentRow + 1);
            } else {
                // Calculate total duration
                var endTime = new Date();
                var totalMs = endTime.getTime() - startTime.getTime();
                var totalTime = time(totalMs);

                //jQuery("div.progress div.bar").css({width: '100%'}).text('Import completed!');
                jQuery("div.progress div.bar").css({width: '100%'}).text('Import completed in ' + totalTime + '!');
                jQuery("div.progress div.bar").attr({'aria-valuenow': '100'});
                //put('Import completed!');
                //put('Import completed in ' + totalTime + '!');
                put('Import completed in ' + totalTime + ' (' + totalEntries + ' entries processed).');
            }
            jQuery("div.console").attr({ scrollTop: jQuery("div.console").attr("scrollHeight") });
        }
    });

}

/**
 * Get a variable from the HTML code
 * @param name  The name of the variable
 */
function getVar(name)
{
    return jQuery("var." + name).text();
}

/**
 * Put a message in the console
 * @param str   The content
 */
function put(str, cls)
{
    c = cls == null ? '' : ' class="' + cls + '"';
    jQuery("div.console").append('<span'+c+'>' + str + '</span>');
}

function two(x) {return ((x>9)?"":"0")+x}

function time(ms) {
    var sec = Math.floor(ms/1000);
    var min = Math.floor(sec/60);
    sec = sec % 60;
    var t = two(sec);

    var hr = Math.floor(min/60);
    min = min % 60;
    t = two(min) + ":" + t;
    return t;
}
