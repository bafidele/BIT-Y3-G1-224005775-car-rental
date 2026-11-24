(() => {
    const form = document.getElementById('vehicle-search-form');
    const grid = document.getElementById('vehicle-grid');
    const search = document.getElementById('vehicle-search');
    const status = document.getElementById('vehicle-status');
    const count = document.getElementById('vehicle-result-count');
    const liveStatus = document.getElementById('vehicle-search-status');
    if (!form || !grid || !search || !status || !count) return;

    const make = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };

    function formatRentalDateTime(dateValue, timeValue) {
        const dateParts = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(dateValue || ''));
        if (!dateParts) return String(dateValue || 'Date not available');

        const [, year, month, day] = dateParts;
        const timeParts = /^(\d{2}):(\d{2})(?::\d{2})?$/.exec(String(timeValue || ''));
        const hours = timeParts ? Number(timeParts[1]) : 0;
        const minutes = timeParts ? Number(timeParts[2]) : 0;
        const calendarDate = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day), hours, minutes));

        if (calendarDate.getUTCFullYear() !== Number(year)
            || calendarDate.getUTCMonth() !== Number(month) - 1
            || calendarDate.getUTCDate() !== Number(day)) {
            return String(dateValue);
        }

        const options = timeParts
            ? { dateStyle: 'medium', timeStyle: 'short', timeZone: 'UTC' }
            : { dateStyle: 'medium', timeZone: 'UTC' };
        return new Intl.DateTimeFormat(undefined, options).format(calendarDate);
    }

    function renderVehicle(vehicle) {
        const card = make('article', 'car-card');
        const image = make('img', 'vehicle-photo');
        image.alt = vehicle.model || 'Rental car';
        image.loading = 'lazy';
        const photo = String(vehicle.photo || '');
        image.src = /^[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$/i.test(photo)
            ? `uploads/${encodeURIComponent(photo)}`
            : 'assets/car-placeholder.svg';
        image.addEventListener('error', () => { image.src = 'assets/car-placeholder.svg'; }, { once: true });
        card.append(image);
        card.append(make('h3', '', vehicle.model || 'Vehicle'));
        const type = make('p');
        type.append(make('strong', '', 'Type: '), document.createTextNode(` ${vehicle.type || ''}`));
        card.append(type);
        const plate = make('p');
        plate.append(make('strong', '', 'Plate: '), document.createTextNode(` ${vehicle.plate_no || ''}`));
        card.append(plate);
        const price = Number(vehicle.daily_rate);
        const rate = make('p');
        rate.append(make('strong', '', 'Price: '), document.createTextNode(` $${Number.isFinite(price) ? price.toFixed(2) : '0.00'} / day`));
        card.append(rate);

        if (vehicle.status === 'available') {
            card.append(make('span', 'stock-availability', 'AVAILABLE NOW'));
            const customer = form.dataset.customer === '1';
            const signedIn = form.dataset.authenticated === '1';
            const action = make('a', signedIn ? 'stock-rent-button' : 'btn-green browse-register-button', signedIn ? 'Rent this car' : 'Create a free account to rent');
            action.href = signedIn
                ? `add_rental.php?vehicle_id=${Number(vehicle.vehicle_id)}${customer ? '&show_terms=1' : ''}`
                : 'register.php';
            card.append(action);
        } else if (vehicle.status === 'rented') {
            const rented = make('button', 'btn-red car-rented-button', 'This car has been rented');
            rented.type = 'button';
            rented.disabled = true;
            card.append(rented);
            const returnInfo = make('p', 'car-return-date');
            returnInfo.append(make('strong', '', 'Expected return: '));
            if (vehicle.expected_return_date) {
                returnInfo.append(document.createTextNode(formatRentalDateTime(
                    vehicle.expected_return_date,
                    vehicle.expected_return_time,
                )));
            } else {
                returnInfo.append(document.createTextNode('Date not available'));
            }
            card.append(returnInfo);
        } else {
            card.append(make('span', 'badge maintenance', 'Under maintenance'));
        }
        return card;
    }

    let timer;
    let activeRequest;
    async function loadVehicles() {
        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();
        const params = new URLSearchParams({ q: search.value, status: status.value });
        liveStatus.textContent = 'Searching cars…';
        try {
            const response = await fetch(`${form.dataset.apiUrl}?${params}`, {
                headers: { Accept: 'application/json' },
                signal: activeRequest.signal,
            });
            const payload = await response.json();
            if (!response.ok || payload.ok !== true) throw new Error(payload.error || 'Cars could not be loaded.');
            const vehicles = payload.data.vehicles || [];
            grid.replaceChildren(...(vehicles.length
                ? vehicles.map(renderVehicle)
                : [make('p', 'empty-state vehicle-empty', 'No cars match your search.') ]));
            count.textContent = `${vehicles.length} vehicle${vehicles.length === 1 ? '' : 's'}`;
            liveStatus.textContent = `${vehicles.length} cars found.`;
        } catch (error) {
            if (error.name === 'AbortError') return;
            liveStatus.textContent = `${error.message} Showing the last loaded results.`;
        }
    }

    search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(loadVehicles, 220);
    });
    status.addEventListener('change', loadVehicles);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadVehicles();
    });
})();
