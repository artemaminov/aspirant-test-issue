$('form').submit(function (e) {
  // prevent the page from submitting like normal
  e.preventDefault(); 

  $.ajax({
      url: '/gettrailers',
      type: 'get',
      data: $(this).serialize(),
      success: function () {
          console.log('Data fetched!');
      },
      error: function () {
          console.log('Failed!');
      }
  });
});