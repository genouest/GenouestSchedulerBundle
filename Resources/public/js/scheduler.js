jQuery.noConflict();

function statusTracker(url)
{
  // Hide refresh message if js enabled
  jQuery('p.refresh').hide();
  
  // Set the timer to ask job status regularly
  setInterval(function() {
    jQuery.getJSON(url, function(data){
      // Redirect to results if job finished
      if (data.shouldRedirect) {
        jQuery(location).prop('href', data.resultsUrl);
        return; // No need to do anything else
      }
      
      // Update status
      jQuery('p.jobStatus span.status').html(data.status);
      
      // Update progress bar
      var pxProgress = ((100-data.progress)*(-2))+1;
      jQuery('div.progressBar').css('background-position', pxProgress+"px 0");
      jQuery('p.jobStatus span.percent').html(data.progress);
    });
  }, 10000)
}
