(() => {
    const form = document.getElementById('html-vehicle-search');
    const search = document.getElementById('html-vehicle-query');
    const status = document.getElementById('html-vehicle-status');
    const grid = document.getElementById('html-vehicle-grid');
    const count = document.getElementById('html-vehicle-count');
    const message = document.getElementById('html-vehicle-status-message');
    if (!form || !search || !status || !grid || !count || !message) return;

    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };

    function formatReturn(date, time) {
        if (!date) return 'Date not available';
        const dateMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(date));
        if (!dateMatch) return String(date);
        const [, year, month, day] = dateMatch;
        const dateObject = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day)));
        const readableDate = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeZone: 'UTC' }).format(dateObject);
        const timeMatch = /^(\d{2}):(\d{2})/.exec(String(time || ''));
        return timeMatch ? `${readableDate} at ${timeMatch[1]}:${timeMatch[2]}` : readableDate;
    }

    function renderCar(car) {
        const card = element('article', 'car-card');
        const image = element('img', 'vehicle-photo');
        const photo = String(car.photo || '');
        image.alt = car.model ? `${car.model} rental car` : 'Rental car';
        image.loading = 'lazy';
        image.src = /^[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$/i.test(photo)
            ? `uploads/${encodeURIComponent(photo)}`
            : 'assets/car-placeholder.svg';
        image.addEventListener('error', () => { image.src = 'assets/car-placeholder.svg'; }, { once: true });

        card.append(image, element('h3', '', car.model || 'Vehicle'));
        card.append(element('p', '', `Type: ${car.type || 'Not specified'}`));
        card.append(element('p', '', `Plate: ${car.plate_no || 'Not specified'}`));
        const rate = Number(car.daily_rate);
        card.append(element('p', '', `Price: $${Number.isFinite(rate) ? rate.toFixed(2) : '0.00'} / day`));

        if (car.status === 'available') {
            card.append(element('span', 'stock-availability', 'AVAILABLE NOW'));
            const bookingLink = element('a', 'stock-rent-button', 'Continue to booking page');
            bookingLink.href = `browse.php?q=${encodeURIComponent(car.model || '')}`;
            card.append(bookingLink);
        } else if (car.status === 'rented') {
            const rented = element('button', 'btn-red car-rented-button', 'This car has been rented');
            rented.type = 'button';
            rented.disabled = true;
            card.append(rented, element('p', 'car-return-date', `Expected return: ${formatReturn(car.expected_return_date, car.expected_return_time)}`));
        } else {
            card.append(element('span', 'badge maintenance', 'Under maintenance'));
        }
        return card;
    }

    let timer;
    let currentRequest;
    async function loadCars() {
        if (currentRequest) currentRequest.abort();
        currentRequest = new AbortController();
        const query = new URLSearchParams({ q: search.value, status: status.value });
        message.textContent = 'Loading car results.';
        try {
            const response = await fetch(`api/vehicles.php?${query}`, {
                headers: { Accept: 'application/json' },
                signal: currentRequest.signal,
            });
            const result = await response.json();
            if (!response.ok || result.ok !== true) throw new Error(result.error || 'Cars could not be loaded.');
            const cars = Array.isArray(result.data?.vehicles) ? result.data.vehicles : [];
            grid.replaceChildren(...(cars.length
                ? cars.map(renderCar)
                : [element('p', 'empty-state', 'No cars match your search.') ]));
            count.textContent = `${cars.length} car${cars.length === 1 ? '' : 's'}`;
            message.textContent = `${cars.length} cars found.`;
        } catch (error) {
            if (error.name === 'AbortError') return;
            grid.replaceChildren(element('p', 'error', 'Could not load the cars. Try again or open the booking page.'));
            count.textContent = 'Cars unavailable';
            message.textContent = 'Car search failed.';
        }
    }

    search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(loadCars, 220);
    });
    status.addEventListener('change', loadCars);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadCars();
    });
    loadCars();
})();
