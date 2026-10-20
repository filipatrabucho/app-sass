import { Typography } from 'antd';

/** Cabeçalho de página: título + subtítulo opcional + ações à direita. */
export default function PageHeader({ title, subtitle, extra }) {
  return (
    <div className="page-header">
      <div>
        <Typography.Title level={3} className="page-header-title">
          {title}
        </Typography.Title>
        {subtitle && <Typography.Text type="secondary">{subtitle}</Typography.Text>}
      </div>
      {extra}
    </div>
  );
}
