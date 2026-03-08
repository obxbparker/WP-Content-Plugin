import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    Card,
    CardBody,
    CardHeader,
    TextControl,
    SelectControl,
    Spinner,
    Notice,
    Modal,
} from '@wordpress/components';
import {
    getTemplateTypes,
    createTemplateType,
    deleteTemplateType,
    setElementorTemplate,
    getBlueprint,
    getMapping,
    saveMapping,
    getElementorTemplates,
} from '../api/client';
import FieldMappingEditor from '../components/FieldMappingEditor';

export default function TemplateSetup() {
    const [types, setTypes] = useState([]);
    const [elementorTemplates, setElementorTemplates] = useState([]);
    const [newTypeName, setNewTypeName] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [activeMappingSlug, setActiveMappingSlug] = useState(null);
    const [activeMapping, setActiveMapping] = useState([]);
    const [activeBlueprint, setActiveBlueprint] = useState([]);
    const [deleteConfirm, setDeleteConfirm] = useState(null);

    const loadData = async () => {
        setLoading(true);
        try {
            const [typesData, templatesData] = await Promise.all([
                getTemplateTypes(),
                getElementorTemplates(),
            ]);
            setTypes(typesData);
            setElementorTemplates(templatesData);
            setError(null);
        } catch (err) {
            setError(err.message);
        }
        setLoading(false);
    };

    useEffect(() => { loadData(); }, []);

    const handleCreate = async () => {
        if (!newTypeName.trim()) return;
        try {
            const newType = await createTemplateType(newTypeName.trim());
            setTypes((prev) => [...prev, newType]);
            setNewTypeName('');
            setSuccess(`Template type "${newType.name}" created.`);
        } catch (err) {
            setError(err.message);
        }
    };

    const handleDelete = async (slug) => {
        try {
            await deleteTemplateType(slug);
            setTypes((prev) => prev.filter((t) => t.slug !== slug));
            setDeleteConfirm(null);
            setSuccess('Template type deleted.');
        } catch (err) {
            setError(err.message);
        }
    };

    const handleSetTemplate = async (slug, templateId) => {
        try {
            const updated = await setElementorTemplate(slug, parseInt(templateId, 10));
            setTypes((prev) =>
                prev.map((t) => (t.slug === slug ? updated : t))
            );
            setSuccess('Elementor template assigned. Field mapping auto-generated.');
        } catch (err) {
            setError(err.message);
        }
    };

    const handleOpenMapping = async (slug) => {
        try {
            const [blueprint, mapping] = await Promise.all([
                getBlueprint(slug),
                getMapping(slug),
            ]);
            setActiveBlueprint(blueprint);
            setActiveMapping(mapping);
            setActiveMappingSlug(slug);
        } catch (err) {
            setError(err.message);
        }
    };

    const handleSaveMapping = async (mapping) => {
        try {
            await saveMapping(activeMappingSlug, mapping);
            setActiveMappingSlug(null);
            setSuccess('Field mapping saved.');
        } catch (err) {
            setError(err.message);
        }
    };

    const templateOptions = [
        { label: '— Select Elementor Template —', value: '0' },
        ...elementorTemplates.map((t) => ({
            label: `${t.title} (${t.type})`,
            value: String(t.id),
        })),
    ];

    if (loading) {
        return (
            <div className="contenthub-wp-loading">
                <Spinner /> Loading template types...
            </div>
        );
    }

    return (
        <div className="contenthub-wp-templates">
            {error && (
                <Notice status="error" isDismissible onDismiss={() => setError(null)}>
                    {error}
                </Notice>
            )}
            {success && (
                <Notice status="success" isDismissible onDismiss={() => setSuccess(null)}>
                    {success}
                </Notice>
            )}

            <h2>Template Types</h2>
            <p>
                Create template types (e.g., "Home", "Landing Page", "Detail"),
                then select an Elementor Library template for each type. The
                plugin will read the template's structure and use it as
                the blueprint for all pages of that type.
            </p>

            {elementorTemplates.length === 0 && (
                <Notice status="warning" isDismissible={false}>
                    No Elementor Library templates found. Save a page as an
                    Elementor template first (in the Elementor editor, use
                    Save as Template), then come back here.
                </Notice>
            )}

            <div className="contenthub-wp-create-type">
                <TextControl
                    label="New Template Type"
                    value={newTypeName}
                    onChange={setNewTypeName}
                    placeholder="e.g., Landing Page"
                    __nextHasNoMarginBottom
                />
                <Button variant="primary" onClick={handleCreate} disabled={!newTypeName.trim()}>
                    Create
                </Button>
            </div>

            <div className="contenthub-wp-type-cards">
                {types.map((type) => (
                    <Card key={type.slug} className="contenthub-wp-type-card">
                        <CardHeader>
                            <h3>{type.name}</h3>
                            <Button
                                variant="tertiary"
                                isDestructive
                                size="small"
                                onClick={() => setDeleteConfirm(type.slug)}
                            >
                                Delete
                            </Button>
                        </CardHeader>
                        <CardBody>
                            <SelectControl
                                label="Elementor Template"
                                value={String(type.elementor_template_id || 0)}
                                options={templateOptions}
                                onChange={(val) =>
                                    handleSetTemplate(type.slug, val)
                                }
                                __nextHasNoMarginBottom
                            />
                            {(type.elementor_template_id > 0) && (
                                <Button
                                    variant="secondary"
                                    onClick={() =>
                                        handleOpenMapping(type.slug)
                                    }
                                >
                                    Edit Field Mapping
                                </Button>
                            )}
                        </CardBody>
                    </Card>
                ))}

                {types.length === 0 && (
                    <p>
                        No template types created yet. Add your first template
                        type above.
                    </p>
                )}
            </div>

            {activeMappingSlug && (
                <Modal
                    title="Field Mapping Editor"
                    onRequestClose={() => setActiveMappingSlug(null)}
                    isFullScreen
                >
                    <FieldMappingEditor
                        blueprint={activeBlueprint}
                        mapping={activeMapping}
                        onSave={handleSaveMapping}
                        onCancel={() => setActiveMappingSlug(null)}
                    />
                </Modal>
            )}

            {deleteConfirm && (
                <Modal
                    title="Confirm Delete"
                    onRequestClose={() => setDeleteConfirm(null)}
                >
                    <p>
                        Are you sure you want to delete this template type? This
                        will also remove all page assignments for this type.
                    </p>
                    <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
                        <Button
                            variant="secondary"
                            onClick={() => setDeleteConfirm(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            isDestructive
                            onClick={() => handleDelete(deleteConfirm)}
                        >
                            Delete
                        </Button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
