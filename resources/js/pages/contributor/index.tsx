import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function ContributorWorkspace() {
    return (
        <>
            <Head title="Contributor Workspace — Filmo" />

            <div className="mx-auto w-full max-w-3xl px-4 py-16 md:px-6">
                <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                    Contributor Workspace
                </p>
                <h1 className="mt-3 text-3xl font-semibold tracking-tight">
                    기여자 워크스페이스
                </h1>
                <p className="mt-4 text-muted-foreground">
                    채널·영상·배우를 추가하고 편집하는 공간입니다. 곧 더 많은
                    기능이 추가됩니다.
                </p>

                <Button className="mt-8" asChild>
                    <Link href="/catalog">공개 카탈로그로 이동</Link>
                </Button>
            </div>
        </>
    );
}
