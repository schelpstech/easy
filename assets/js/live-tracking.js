(function () {
  'use strict';
  var root = document.querySelector('[data-live-tracking]');
  if (!root) return;
  var title = root.querySelector('[data-live-title]');
  var message = root.querySelector('[data-live-message]');
  var age = root.querySelector('[data-live-age]');
  var map = root.querySelector('[data-live-map]');
  var frame = map.querySelector('iframe');
  var link = map.querySelector('[data-live-link]');
  function update() {
    fetch(root.dataset.endpoint, {credentials: 'same-origin', cache: 'no-store'})
      .then(function (response) { return response.json(); })
      .then(function (body) {
        if (!body.ok || !body.live || !body.location) {
          title.textContent = 'Live location is not being shared'; message.textContent = 'The verified milestone timeline below remains available.';
          age.textContent = 'Private by default'; map.hidden = true; return;
        }
        var point = body.location; var lat = Number(point.latitude); var lng = Number(point.longitude); var delta = 0.012;
        title.textContent = 'Rider location is active'; message.textContent = 'Approximate device location. Accuracy: ' + (point.accuracy_m ? 'about ' + point.accuracy_m + ' m' : 'device estimate unavailable') + '.';
        age.textContent = point.age_seconds < 60 ? 'Updated just now' : 'Updated ' + Math.floor(point.age_seconds / 60) + ' min ago';
        frame.src = 'https://www.openstreetmap.org/export/embed.html?bbox=' + encodeURIComponent((lng-delta)+','+(lat-delta)+','+(lng+delta)+','+(lat+delta)) + '&layer=mapnik&marker=' + encodeURIComponent(lat+','+lng);
        link.href = 'https://www.openstreetmap.org/?mlat=' + encodeURIComponent(lat) + '&mlon=' + encodeURIComponent(lng) + '#map=15/' + encodeURIComponent(lat) + '/' + encodeURIComponent(lng);
        map.hidden = false;
      }).catch(function () { title.textContent = 'Live location is temporarily unavailable'; message.textContent = 'The verified milestone timeline below is unaffected.'; age.textContent = 'Retrying'; map.hidden = true; });
  }
  update(); window.setInterval(update, 15000);
}());
