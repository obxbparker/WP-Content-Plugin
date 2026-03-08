import { useState } from '@wordpress/element';
import { Button, TextControl, SelectControl } from '@wordpress/components';

const FIELD_TYPES = [
    { label: 'Text', value: 'text' },
    { label: 'Repeater (list)', value: 'repeater' },
    { label: 'Group Item', value: 'group_item' },
];

export default function FieldMappingEditor({
    blueprint,
    mapping,
    onSave,
    onCancel,
}) {
    const [entries, setEntries] = useState(
        mapping.length > 0 ? [...mapping] : []
    );

    const updateEntry = (index, field, value) => {
        setEntries((prev) =>
            prev.map((entry, i) =>
                i === index ? { ...entry, [field]: value } : entry
            )
        );
    };

    return (
        <div className="contenthub-wp-mapping-editor">
            <p>
                Map each Elementor widget slot to a named content field. The
                content field name is what the scraper/generator/manual form will
                use. You can rename fields to whatever makes sense for your
                template.
            </p>

            <table className="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style={{ width: '15%' }}>Widget Type</th>
                        <th style={{ width: '15%' }}>Widget Field</th>
                        <th style={{ width: '25%' }}>Current Value</th>
                        <th style={{ width: '25%' }}>Content Field Name</th>
                        <th style={{ width: '15%' }}>Field Type</th>
                    </tr>
                </thead>
                <tbody>
                    {entries.map((entry, i) => {
                        const slot = blueprint.find(
                            (s) =>
                                s.slot_id === entry.slot_id &&
                                s.field === entry.field
                        );

                        return (
                            <tr key={i}>
                                <td>
                                    <code>{entry.widget_type}</code>
                                </td>
                                <td>
                                    <code>{entry.field}</code>
                                </td>
                                <td className="contenthub-wp-mapping-preview">
                                    {slot?.current_value
                                        ? Array.isArray(slot.current_value)
                                            ? `${slot.current_value.length} items`
                                            : String(slot.current_value).substring(0, 80)
                                        : '—'}
                                </td>
                                <td>
                                    <TextControl
                                        value={entry.content_field_name}
                                        onChange={(val) =>
                                            updateEntry(
                                                i,
                                                'content_field_name',
                                                val.replace(/\s+/g, '_').toLowerCase()
                                            )
                                        }
                                        __nextHasNoMarginBottom
                                    />
                                </td>
                                <td>
                                    <SelectControl
                                        value={entry.content_field_type}
                                        options={FIELD_TYPES}
                                        onChange={(val) =>
                                            updateEntry(
                                                i,
                                                'content_field_type',
                                                val
                                            )
                                        }
                                        __nextHasNoMarginBottom
                                    />
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>

            <div
                style={{
                    display: 'flex',
                    gap: '8px',
                    justifyContent: 'flex-end',
                    marginTop: '16px',
                }}
            >
                <Button variant="secondary" onClick={onCancel}>
                    Cancel
                </Button>
                <Button variant="primary" onClick={() => onSave(entries)}>
                    Save Mapping
                </Button>
            </div>
        </div>
    );
}
