import { Spin } from 'antd';

export default function FullPageLoader() {
  return (
    <div className="full-page-center">
      <Spin size="large" />
    </div>
  );
}
