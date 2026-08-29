(function () {
  'use strict';
  var root = document.querySelector('[data-rider-tracking]');
  if (!root) return;
  var start = root.querySelector('[data-live-start]');
  var stop = root.querySelector('[data-live-stop]');
  var status = root.querySelector('[data-live-status]');
  var share = root.querySelector('#share-public');
  var watchId = null;
  var lastSent = 0;

  function setStatus(message, active) {
    status.textContent = message;
    root.classList.toggle('is-live', Boolean(active));
  }

  function post(values) {
    values.set('_token', root.dataset.token);
    return fetch(root.dataset.endpoint, {
      method: 'POST', credentials: 'same-origin',
      headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: values.toString()
    }).then(function (response) { return response.json().then(function (body) { if (!response.ok || !body.ok) throw new Error(body.message || 'Location update failed.'); return body; }); });
  }

  function send(position) {
    if (Date.now() - lastSent < 12000) return;
    lastSent = Date.now();
    var values = new URLSearchParams({
      shipment_id: root.dataset.shipment, latitude: String(position.coords.latitude), longitude: String(position.coords.longitude),
      accuracy_m: String(position.coords.accuracy || ''), speed_mps: String(position.coords.speed || ''),
      heading_degrees: String(position.coords.heading || ''), recorded_at: new Date(position.timestamp).toISOString(),
      share_public: share.checked ? '1' : '0'
    });
    post(values).then(function () { setStatus('Location shared at ' + new Date().toLocaleTimeString() + (share.checked ? ' · public trip view enabled' : ' · dispatch only'), true); })
      .catch(function (error) { setStatus(error.message, false); });
  }

  start.addEventListener('click', function () {
    if (!navigator.geolocation) { setStatus('This device does not support location services.', false); return; }
    start.disabled = true; stop.disabled = false; setStatus('Requesting your device location…', true);
    watchId = navigator.geolocation.watchPosition(send, function (error) { setStatus(error.message || 'Location permission was not granted.', false); start.disabled = false; stop.disabled = true; }, {enableHighAccuracy: true, maximumAge: 5000, timeout: 15000});
  });
  stop.addEventListener('click', function () {
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
    watchId = null; start.disabled = false; stop.disabled = true;
    post(new URLSearchParams({action: 'stop'})).then(function () { setStatus('Sharing is off.', false); }).catch(function (error) { setStatus(error.message, false); });
  });
  window.addEventListener('pagehide', function () { if (watchId !== null) navigator.geolocation.clearWatch(watchId); });
}());
