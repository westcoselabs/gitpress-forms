import type { Field } from '../shared/types';
export function FieldPreview({ field }: { field: Field }) {
  const { type, label, placeholder, required } = field;
  const heading = <label>{label}{required ? <span className="required"> *</span> : null}</label>;
  if (type === 'section') return <div className="field-preview"><h3>{label}</h3><p>{field.help}</p></div>;
  if (type === 'html') return <div className="field-preview"><span className="field-preview-code">HTML</span><p>{field.html ? field.html.replace(/<[^>]*>/g, '') : 'Add custom content in Input Customization'}</p></div>;
  if (type === 'hidden') return <div className="field-preview"><span className="field-preview-code">HIDDEN</span> {label}</div>;
  if (type === 'recaptcha') return <div className="field-preview">{heading}<div className="preview-recaptcha"><span>✓</span><strong>I'm not a robot</strong><small>reCAPTCHA</small></div></div>;
  if (['radio', 'checkbox'].includes(type)) return <div className="field-preview">{heading}<div className="preview-options">{field.options.map((o, i) => <label key={i}><input disabled type={type} /> {o.label}</label>)}</div></div>;
  if (['terms', 'gdpr'].includes(type)) return <div className="field-preview">{heading}<label className="preview-choice"><input disabled type="checkbox" />{field.help || 'I agree to the terms and conditions'}</label></div>;
  if (type === 'ranking') return <div className="field-preview">{heading}<ol className="preview-ranking">{field.options.map(o => <li key={o.value}>{o.label}</li>)}</ol></div>;
  if (type === 'rating' || type === 'nps') return <div className="field-preview">{heading}<div className="preview-scores">{Array.from({ length: type === 'rating' ? field.max || 5 : 11 }, (_, i) => <span key={i}>{type === 'rating' ? '★' : i}</span>)}</div></div>;
  if (['select', 'country', 'multiselect'].includes(type)) return <div className="field-preview">{heading}<select disabled><option>{placeholder || 'Select an option'}</option></select></div>;
  if (type === 'name' || type === 'address') return <div className="field-preview">{heading}<div className="preview-compound">{(type === 'name' ? ['First Name', 'Last Name'] : ['Street Address', 'City', 'State / Region', 'Postal Code']).map(part => <input disabled key={part} placeholder={part} />)}</div></div>;
  if (type === 'textarea' || type === 'richtext') return <div className="field-preview">{heading}<textarea disabled rows={3} placeholder={placeholder} /></div>;
  if (type === 'file' || type === 'image') return <div className="field-preview">{heading}<div className="preview-upload"><span>Choose {type === 'image' ? 'image' : 'file'}</span><small>No file chosen</small></div></div>;
  if (type === 'signature') return <div className="field-preview">{heading}<div className="preview-signature">Sign here</div></div>;
  if (type === 'grid') return <div className="field-preview">{heading}<div className="preview-grid"><span></span>{field.options.map((o, i) => <small key={i}>{o.label}</small>)}{(field.rows || ['Row 1']).map((row, i) => <div className="preview-grid-row" key={i}><small>{row}</small>{field.options.map((o, j) => <input disabled type="radio" key={j} aria-label={`${row} ${o.label}`} />)}</div>)}</div></div>;
  return <div className="field-preview">{heading}<input disabled type={type === 'slider' ? 'range' : type === 'color' ? 'color' : type === 'date' ? 'date' : 'text'} placeholder={type === 'calculation' ? field.formula || '0' : placeholder} />{field.help ? <small>{field.help}</small> : null}</div>;
}
