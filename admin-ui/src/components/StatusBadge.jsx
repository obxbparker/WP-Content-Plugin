export default function StatusBadge({ status }) {
    const config = {
        deployed: { label: 'Deployed', color: '#00a32a', bg: '#edfaef' },
        ready: { label: 'Ready', color: '#dba617', bg: '#fcf9e8' },
        draft: { label: 'Draft', color: '#757575', bg: '#f0f0f0' },
        template: { label: 'Template', color: '#8c5dc1', bg: '#f3eefa' },
        '': { label: 'No Content', color: '#a7aaad', bg: '#f6f7f7' },
    };

    const { label, color, bg } = config[status] || config[''];

    return (
        <span
            style={{
                display: 'inline-block',
                padding: '2px 8px',
                borderRadius: '3px',
                fontSize: '12px',
                fontWeight: 500,
                color,
                backgroundColor: bg,
            }}
        >
            {label}
        </span>
    );
}
