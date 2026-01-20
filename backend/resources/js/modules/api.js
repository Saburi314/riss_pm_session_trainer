export async function postGenerate(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    });
    if (!response.ok) throw new Error('Network response was not ok');
    return await response.json();
}

export async function postScore(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    });
    if (!response.ok) throw new Error('Network response was not ok');
    return await response.json();
}
