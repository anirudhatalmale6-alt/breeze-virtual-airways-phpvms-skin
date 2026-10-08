{{--
  Airport search boxes (departure / arrival) on the flights page.

  Breeze override of the seven partial. Two changes only, everything Ray added -
  preload, openOnFocus, shouldLoad, the bold placeholder - is deliberately kept.

  1. The placeholder text. TomSelect's `placeholder` option OVERRIDES the
     placeholder attribute on the <select>, so shortening the markup had no
     effect; this line is the one that actually shows. "Please select from the
     drop down list" is 37 characters and does not fit the sidebar column.

  2. The placeholder colour. It was hard-coded #FFFFFF, which is white text on a
     white control in light mode, i.e. invisible - the comment next to it said
     "solid black" so the colour looks like a slip rather than the intent. Using
     var(--bs-body-color) keeps it readable in BOTH themes: near-black on the
     light skin, white on the dark one, which is what the original was reaching
     for.
--}}
<style>
  /* Placeholder stays bold, but takes its colour from the active theme so it is
     legible in light mode as well as dark. */
  .ts-wrapper .ts-control input::placeholder,
  .ts-wrapper .ts-control .placeholder {
    font-weight: bold !important;
    color: var(--bs-body-color) !important;
    opacity: 1 !important;
  }
</style>
<script>

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll("select.airport_search").forEach(function(element) {
      new TomSelect(element, {
        valueField: 'id',
        labelField: 'description',
        searchField: 'description',
        placeholder: 'Select airport',
        preload: true,
        openOnFocus: true,
        shouldLoad: function(query) {
          return true;
        },
        load: function(query, callback) {
          var url = new URL('{{ Config::get("app.url") }}/api/airports/search');
          var params = {
            search: query,
            hubs: element.classList.contains('hubs_only') ? 1 : 0,
            page: 1,
            orderBy: 'id',
            sortedBy: 'asc'
          };
          Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
          fetch(url)
            .then(response => response.json())
            .then(json => {
              callback(json.data);
            }).catch(function () {
              callback();
            });
        }
      });
    });
  });
</script>
