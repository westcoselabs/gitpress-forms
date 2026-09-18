declare const wp: {
  blocks: { registerBlockType: (name: string, settings: object) => void };
  element: { createElement: (...args: any[]) => any; Fragment: any; useEffect: (fn: () => void, deps: unknown[]) => void; useState: <T>(value: T) => [T, (value: T) => void] };
  blockEditor: { InspectorControls: any; useBlockProps: () => object };
  components: { SelectControl: any; Placeholder: any; PanelBody: any; RangeControl: any; ColorPalette: any; Notice: any };
  apiFetch: (args: { path: string }) => Promise<any[]>;
};
const { createElement: h, useEffect, useState, Fragment } = wp.element;
type Attributes = { id: number; primary?: string; background?: string; radius?: number; maxWidth?: number };
const attributes = { id: { type: 'number', default: 0 }, primary: { type: 'string' }, background: { type: 'string' }, radius: { type: 'number' }, maxWidth: { type: 'number' } };
wp.blocks.registerBlockType('gitpress-forms/form', {
  apiVersion: 3, title: 'GitPress Form', icon: 'feedback', category: 'widgets', attributes,
  supports: { align: ['wide', 'full'], spacing: { margin: true, padding: true } },
  edit: ({ attributes: a, setAttributes }: { attributes: Attributes; setAttributes: (value: Partial<Attributes>) => void }) => {
    const [forms, setForms] = useState<{ id: number; title: string; status: string }[]>([]); const [error, setError] = useState('');
    useEffect(() => { wp.apiFetch({ path: '/gitpress-forms/v1/forms' }).then(setForms).catch(() => setError('You need form-management access to select a form.')); }, []);
    const props = wp.blockEditor.useBlockProps();
    return h(Fragment, null,
      h(wp.blockEditor.InspectorControls, null, h(wp.components.PanelBody, { title: 'Form appearance', initialOpen: true },
        h('p', null, 'These overrides apply to this block. Clear a value to inherit the form design.'),
        ...(['primary', 'background'] as const).map(key => h('div', { key }, h('h3', null, key === 'primary' ? 'Accent color' : 'Background'), h(wp.components.ColorPalette, { value: a[key], onChange: (value?: string) => setAttributes({ [key]: value }), clearable: true }))),
        h(wp.components.RangeControl, { label: 'Corner radius', value: a.radius, min: 0, max: 40, allowReset: true, onChange: (radius?: number) => setAttributes({ radius }) }),
        h(wp.components.RangeControl, { label: 'Maximum width', value: a.maxWidth, min: 280, max: 2000, allowReset: true, onChange: (maxWidth?: number) => setAttributes({ maxWidth }) }))),
      h('div', props, h(wp.components.Placeholder, { icon: 'feedback', label: 'GitPress Forms', instructions: 'Choose a published form. WordPress renders the current fields on your website.' },
        error ? h(wp.components.Notice, { status: 'error', isDismissible: false }, error) : h(wp.components.SelectControl, { label: 'Form', value: a.id, options: [{ label: 'Select a form…', value: 0 }, ...forms.filter(f => f.status === 'published').map(f => ({ label: f.title, value: f.id }))], onChange: (value: string) => setAttributes({ id: Number(value) }) }))));
  }, save: () => null,
});
