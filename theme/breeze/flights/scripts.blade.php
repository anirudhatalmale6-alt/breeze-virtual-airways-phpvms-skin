{{--
  Flights page scripts - Breeze override of the seven partial.

  The "Aircraft Booking" box (shown because bids.block_aircraft is on) came up
  EMPTY for anyone on breezevirtualairways.org without the www: the aircraft list
  was fetched from the hard-coded www address with an X-API-KEY header, which is a
  cross-origin request needing a CORS preflight the API does not answer, and the
  fetch had no .catch(), so it failed silently. Changes, everything else stock:

    1. Fetch from the page's own origin, so both addresses work.
    2. A "Select aircraft" searchable dropdown (same TomSelect as the airport
       boxes), created up front so the box is never a blank input.
    3. If loading fails, say so in the box instead of showing nothing.
    4. "Book Aircraft" with nothing chosen asks the pilot to pick one, rather than
       booking with an empty aircraft.
--}}
@section('scripts')
    @parent
    @if (setting('bids.block_aircraft', false))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // The My Bids page includes this script but NOT the booking box.
                // new bootstrap.Modal(null) throws there (stock phpVMS too), which
                // killed the whole handler so Remove Bid did nothing. Only build
                // the box where it exists; the bid buttons are wired up regardless.
                const modalEl = document.getElementById('bidModal');
                const bidModal = modalEl ? new bootstrap.Modal(modalEl) : null;
                let aircrafts = [{
                    id: 0,
                    text: 'Loading Aircrafts...'
                }];
                let sel = document.getElementById('aircraft_select');
                const acSelect = sel ? new TomSelect(sel, {
                    options: [],
                    valueField: 'id',
                    labelField: 'text',
                    searchField: 'text',
                    placeholder: 'Select aircraft',
                    maxOptions: 500
                }) : null;
                const setAcPlaceholder = (text) => {
                    if (!acSelect) return;
                    acSelect.settings.placeholder = text;
                    if (acSelect.control_input) acSelect.control_input.setAttribute('placeholder', text);
                };

                document.querySelectorAll("button.save_flight").forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();

                        const btn = e.target;
                        const class_name = btn.getAttribute(
                            'x-saved-class'); // classname to use is set on the element
                        const flight_id = btn.getAttribute('x-id');
                        if (sel) {
                            sel.setAttribute('x-saved-class', class_name);
                            sel.setAttribute('x-id', flight_id);
                        }

                        if (!btn.classList.contains(class_name) && !bidModal) {
                            // Adding a bid on a page without the booking box:
                            // book without an aircraft rather than doing nothing.
                            phpvms.bids.addBid(flight_id).then(() => {
                                btn.classList.add(class_name);
                                location.reload();
                            }).catch((error) => {
                                if (error.response && error.response.data)
                                    alert(`Error adding bid: ${error.response.data.details}`);
                                else alert(`Error adding bid: ${error.message}`);
                            });
                        } else if (!btn.classList.contains(class_name)) {

                            bidModal.show();
                            acSelect.clear(true);
                            acSelect.clearOptions();
                            setAcPlaceholder('Loading aircraft...');

                            fetch(new URL(`/api/flights/${flight_id}/aircraft`, window.location.origin), {
                                    headers: {
                                        'X-API-KEY': document.querySelector('meta[name="api-key"]')
                                            .getAttribute('content')
                                    }
                                })
                                .then(response => {
                                    if (!response.ok) throw new Error('HTTP ' + response.status);
                                    return response.json();
                                })
                                .then(data => {
                                    data = Array.isArray(data) ? data : (data.data || []);
                                    aircrafts = [];
                                    data.forEach(ac => {
                                        const text =
                                            `[${ac.icao}] ${ac.registration} ${ac.registration !== ac.name ? ` ${ac.name}` : ''}`;
                                        aircrafts.push({
                                            id: ac.id,
                                            text: text
                                        });
                                    });
                                    acSelect.addOptions(aircrafts);
                                    setAcPlaceholder(aircrafts.length ?
                                        'Select aircraft' : 'No aircraft available for this flight');
                                })
                                .catch(err => {
                                    console.error('Aircraft list failed to load', err);
                                    setAcPlaceholder('Could not load aircraft - please refresh');
                                });
                        } else {
                            phpvms.bids.removeBid(flight_id).then(() => {
                                console.log('successfully removed flight');
                                btn.classList.remove(class_name);
                                location.reload();
                            }).catch((error) => {
                                if (error.response && error.response.data)
                                    alert(`Error removing bid: ${error.response.data.details}`);
                                else alert(`Error removing bid: ${error.message}`);
                            });
                        }
                    });
                });

                // The booking box's own buttons only exist where the box does.
                if (modalEl) {
                    document.getElementById('btn-close').addEventListener('click', () => {
                        bidModal.hide();
                    });

                    // Bootstrap closes a data-bs-dismiss button's modal in the CAPTURE
                    // phase, before this handler runs, so it cannot be cancelled here.
                    // Take the auto-close off and close it only once a booking proceeds.
                    const bookBtn = document.getElementById('with_aircraft');
                    bookBtn.removeAttribute('data-bs-dismiss');
                    bookBtn.addEventListener('click', () => {
                        const ac_id = acSelect.getValue();
                        if (!ac_id) {
                            alert('Please select an aircraft first, or choose Don\'t Book Aircraft.');
                            acSelect.focus();
                            return;
                        }
                        bidModal.hide();
                        const flight_id = sel.getAttribute('x-id');
                        const class_name = sel.getAttribute('x-saved-class');
                        phpvms.bids.addBid(flight_id, ac_id).then(() => {
                            document.querySelector(`button.save_flight[x-id="${flight_id}"]`).classList.add(
                                class_name);
                            location.reload();
                        }).catch((error) => {
                            if (error.response && error.response.data)
                                alert(`Error adding bid: ${error.response.data.details}`);
                            else alert(`Error adding bid: ${error.message}`);
                        });
                    });

                    document.getElementById('without_aircraft').addEventListener('click', () => {
                        const flight_id = sel.getAttribute('x-id');
                        const class_name = sel.getAttribute('x-saved-class');
                        phpvms.bids.addBid(flight_id).then(() => {
                            document.querySelector(`button.save_flight[x-id="${flight_id}"]`).classList.add(
                                class_name);
                            location.reload();
                        }).catch((error) => {
                            if (error.response && error.response.data)
                                alert(`Error adding bid: ${error.response.data.details}`);
                            else alert(`Error adding bid: ${error.message}`);
                        });
                    });
                }
            });
        </script>
    @else
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll("button.save_flight").forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();

                        const btn = e.target;
                        const class_name = btn.getAttribute(
                            'x-saved-class'); // classname to use is set on the element
                        const flight_id = btn.getAttribute('x-id');

                        if (!btn.classList.contains(class_name)) {
                            phpvms.bids.addBid(flight_id).then(() => {
                                btn.classList.add(class_name);
                                location.reload();

                            }).catch((error) => {
                                if (error.response && error.response.data)
                                    alert(`Error adding bid: ${error.response.data.details}`);
                                else alert(`Error adding bid: ${error.message}`);
                            });
                        } else {
                            phpvms.bids.removeBid(flight_id).then(() => {
                                btn.classList.remove(class_name);
                                location.reload();
                            }).catch((error) => {
                                if (error.response && error.response.data)
                                    alert(`Error removing bid: ${error.response.data.details}`);
                                else alert(`Error removing bid: ${error.message}`);
                            });
                        }
                    });
                });
            });
        </script>
    @endif
    @include('scripts.airport_search')
@endsection
