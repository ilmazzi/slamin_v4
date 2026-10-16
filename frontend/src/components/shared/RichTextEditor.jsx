import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
import Underline from '@tiptap/extension-underline'
import { EditorContent, useEditor } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import {
  AlignCenter,
  AlignJustify,
  AlignLeft,
  AlignRight,
  Bold,
  Heading2,
  Heading3,
  Italic,
  LinkIcon,
  List,
  ListOrdered,
  Quote,
  Redo2,
  Strikethrough,
  UnderlineIcon,
  Undo2,
} from 'lucide-react'
import { useEffect } from 'react'
import { sanitizeRichText, toEditorDocument } from '../../app/content/richText.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './RichTextEditor.module.css'

function ToolbarButton({ active = false, disabled = false, label, onClick, children }) {
  return (
    <button
      type="button"
      className={[styles.toolbarButton, active ? styles.active : ''].filter(Boolean).join(' ')}
      aria-label={label}
      title={label}
      aria-pressed={active}
      disabled={disabled}
      onClick={onClick}
    >
      {children}
    </button>
  )
}

export default function RichTextEditor({ value, onChange, ariaLabel, minHeight = '280px' }) {
  const { t } = useI18n()
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: { levels: [2, 3] },
        link: false,
        underline: false,
      }),
      Underline,
      Link.configure({
        openOnClick: false,
        autolink: true,
        linkOnPaste: true,
        HTMLAttributes: { rel: 'noopener noreferrer nofollow' },
      }),
      TextAlign.configure({
        types: ['heading', 'paragraph'],
        alignments: ['left', 'center', 'right', 'justify'],
      }),
    ],
    content: toEditorDocument(value),
    immediatelyRender: false,
    editorProps: {
      attributes: {
        'aria-label': ariaLabel,
        class: styles.editable,
        style: `min-height: ${minHeight}`,
      },
    },
    onUpdate: ({ editor: currentEditor }) => {
      onChange(currentEditor.isEmpty ? '' : sanitizeRichText(currentEditor.getHTML()))
    },
  })

  useEffect(() => {
    if (!editor) {
      return
    }

    const nextContent = toEditorDocument(value)
    if (editor.getHTML() !== nextContent) {
      editor.commands.setContent(nextContent, { emitUpdate: false })
    }
  }, [editor, value])

  if (!editor) {
    return null
  }

  function editLink() {
    const currentHref = editor.getAttributes('link').href || ''
    const href = window.prompt(t('rich_text.link_prompt'), currentHref)

    if (href === null) {
      return
    }

    if (href.trim() === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run()
      return
    }

    editor.chain().focus().extendMarkRange('link').setLink({ href: href.trim() }).run()
  }

  const labels = {
    bold: t('rich_text.bold'),
    italic: t('rich_text.italic'),
    underline: t('rich_text.underline'),
    strike: t('rich_text.strike'),
    heading2: t('rich_text.heading_2'),
    heading3: t('rich_text.heading_3'),
    quote: t('rich_text.quote'),
    bulletList: t('rich_text.bullet_list'),
    orderedList: t('rich_text.ordered_list'),
    alignLeft: t('rich_text.align_left'),
    alignCenter: t('rich_text.align_center'),
    alignRight: t('rich_text.align_right'),
    alignJustify: t('rich_text.align_justify'),
    link: t('rich_text.link'),
    undo: t('rich_text.undo'),
    redo: t('rich_text.redo'),
  }

  return (
    <div className={styles.editor}>
      <div className={styles.toolbar} role="toolbar" aria-label={t('rich_text.toolbar')}>
        <div className={styles.group}>
          <ToolbarButton label={labels.bold} active={editor.isActive('bold')} onClick={() => editor.chain().focus().toggleBold().run()}><Bold /></ToolbarButton>
          <ToolbarButton label={labels.italic} active={editor.isActive('italic')} onClick={() => editor.chain().focus().toggleItalic().run()}><Italic /></ToolbarButton>
          <ToolbarButton label={labels.underline} active={editor.isActive('underline')} onClick={() => editor.chain().focus().toggleUnderline().run()}><UnderlineIcon /></ToolbarButton>
          <ToolbarButton label={labels.strike} active={editor.isActive('strike')} onClick={() => editor.chain().focus().toggleStrike().run()}><Strikethrough /></ToolbarButton>
        </div>
        <div className={styles.group}>
          <ToolbarButton label={labels.heading2} active={editor.isActive('heading', { level: 2 })} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}><Heading2 /></ToolbarButton>
          <ToolbarButton label={labels.heading3} active={editor.isActive('heading', { level: 3 })} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}><Heading3 /></ToolbarButton>
          <ToolbarButton label={labels.quote} active={editor.isActive('blockquote')} onClick={() => editor.chain().focus().toggleBlockquote().run()}><Quote /></ToolbarButton>
        </div>
        <div className={styles.group}>
          <ToolbarButton label={labels.bulletList} active={editor.isActive('bulletList')} onClick={() => editor.chain().focus().toggleBulletList().run()}><List /></ToolbarButton>
          <ToolbarButton label={labels.orderedList} active={editor.isActive('orderedList')} onClick={() => editor.chain().focus().toggleOrderedList().run()}><ListOrdered /></ToolbarButton>
          <ToolbarButton label={labels.link} active={editor.isActive('link')} onClick={editLink}><LinkIcon /></ToolbarButton>
        </div>
        <div className={styles.group}>
          <ToolbarButton label={labels.alignLeft} active={editor.isActive({ textAlign: 'left' })} onClick={() => editor.chain().focus().setTextAlign('left').run()}><AlignLeft /></ToolbarButton>
          <ToolbarButton label={labels.alignCenter} active={editor.isActive({ textAlign: 'center' })} onClick={() => editor.chain().focus().setTextAlign('center').run()}><AlignCenter /></ToolbarButton>
          <ToolbarButton label={labels.alignRight} active={editor.isActive({ textAlign: 'right' })} onClick={() => editor.chain().focus().setTextAlign('right').run()}><AlignRight /></ToolbarButton>
          <ToolbarButton label={labels.alignJustify} active={editor.isActive({ textAlign: 'justify' })} onClick={() => editor.chain().focus().setTextAlign('justify').run()}><AlignJustify /></ToolbarButton>
        </div>
        <div className={styles.group}>
          <ToolbarButton label={labels.undo} disabled={!editor.can().chain().focus().undo().run()} onClick={() => editor.chain().focus().undo().run()}><Undo2 /></ToolbarButton>
          <ToolbarButton label={labels.redo} disabled={!editor.can().chain().focus().redo().run()} onClick={() => editor.chain().focus().redo().run()}><Redo2 /></ToolbarButton>
        </div>
      </div>
      <EditorContent editor={editor} />
    </div>
  )
}
