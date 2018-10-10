(function () {

  // Build toc as an array.
  var tocList = function ($listItems) {
    var list = [];
    $listItems.each(function () {
      var $this   = $(this);
      properties = {
        name: $this.data('name'),
        url: $this.data('url')
      };
      list.push(properties);
    });
    return list;
  };

  // Setup easy-autocomplete options.
  var options = {
    data: tocList($('#kss-toc li')),
    placeholder: "Search",
    getValue: function(element) {
      return element.name;
    },
    list: {
      match: {
        enabled: true
      }
    },
    template: {
      type: "links",
      fields: {
        link: "url"
      }
    }
  };

  // Initiate the search field.
  $('#kss-search').easyAutocomplete(options);
})();
