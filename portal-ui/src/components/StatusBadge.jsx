export default function StatusBadge({ status }) {
    const config = {
        deployed: { label: 'Deployed', color: '#00a32a', bg: '#edfaef' },
        ready: { label: 'Ready', color: '#dba617', bg: '#fcf9e8' },
        draft: { label: 'Draft', color: '#757575', bg: '#f0f0f0' },
        '': { label: 'No Content', color: '#a7aaad', bg: '#f6f7f7' },
    };

    const { label, color, bg } = config[status] || config[''];

    return (
        <span
            className="portal-badge"
            style={{ color, backgroundColor: bg }}
        >
            {label}
        </span>
    );
}
