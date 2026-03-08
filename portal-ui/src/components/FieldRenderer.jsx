export function formatFieldLabel(name) {
    return name
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

export function RepeaterField({ name, widgetType, value, onChange }) {
    const isAccordion = ['accordion', 'toggle', 'tabs'].some((t) =>
        widgetType?.includes(t)
    ) || name.includes('faq') || name.includes('tab');

    const addItem = () => {
        const newItem = isAccordion ? { title: '', content: '' } : { text: '' };
        onChange([...value, newItem]);
    };

    const removeItem = (index) => {
        onChange(value.filter((_, i) => i !== index));
    };

    const updateItem = (index, field, val) => {
        onChange(
            value.map((item, i) =>
                i === index ? { ...item, [field]: val } : item
            )
        );
    };

    return (
        <div className="portal-repeater">
            <label className="portal-field-label">
                {formatFieldLabel(name)} ({value.length} items)
            </label>
            {value.map((item, i) => (
                <div key={i} className="portal-repeater-item">
                    {isAccordion ? (
                        <>
                            <div className="portal-field">
                                <label className="portal-field-label">Title</label>
                                <input
                                    type="text"
                                    value={item.title || ''}
                                    onChange={(e) => updateItem(i, 'title', e.target.value)}
                                />
                            </div>
                            <div className="portal-field">
                                <label className="portal-field-label">Content</label>
                                <textarea
                                    rows={2}
                                    value={item.content || ''}
                                    onChange={(e) => updateItem(i, 'content', e.target.value)}
                                />
                            </div>
                        </>
                    ) : (
                        <div className="portal-field">
                            <label className="portal-field-label">Item {i + 1}</label>
                            <input
                                type="text"
                                value={item.text || (typeof item === 'string' ? item : '')}
                                onChange={(e) =>
                                    typeof item === 'string'
                                        ? onChange(value.map((x, j) => (j === i ? e.target.value : x)))
                                        : updateItem(i, 'text', e.target.value)
                                }
                            />
                        </div>
                    )}
                    <button
                        type="button"
                        className="portal-btn-link portal-btn-danger"
                        onClick={() => removeItem(i)}
                    >
                        Remove
                    </button>
                </div>
            ))}
            <button type="button" className="portal-btn portal-btn-secondary portal-btn-sm" onClick={addItem}>
                + Add Item
            </button>
        </div>
    );
}
